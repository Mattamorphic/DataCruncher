<?php

namespace mfmbarber\DataCruncher\Commands;

use mfmbarber\DataCruncher\Helpers\DataSource;
use mfmbarber\DataCruncher\Processor;
use mfmbarber\DataCruncher\Config\Validation;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RunQueryCommand extends Command
{
    /**
     * Configure the command and optional arguments / help text
    **/
    protected function configure()
    {
        //...
        $this->setName('query')
            ->setDescription('Run query')
            ->setHelp('Run a query and return the results');

        $this->addArgument('source', InputArgument::REQUIRED, 'The source to query (currently only files)');

        // TODO make this optional once we finish the streaming to the output (CSV string)
        $this->addArgument('destination', InputArgument::REQUIRED, 'The destination for the result (currently only files)');

        $this->addOption('select', 's', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'The fields to select from the source', []);
        $this->addOption('timer', 't', InputOption::VALUE_NONE, 'Time the process');

        $this->addOption('node', null, InputOption::VALUE_REQUIRED, 'The name of the node element that represents a row');
        $this->addOption('parent', null, InputOption::VALUE_REQUIRED, 'The name of the parent element to the node rows');


    }
    /**
     * Execute the console command, and run through the procedural process
     *
     * @param InputInterface    $input      The input interface for the console object
     * @param OutputInterface   $output     The output interface for the console object
     *
     * @return null
    **/
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Execute DataCruncher Query');
        $query = Processor::generate('segmentation', 'query');

        // Get the source and destination
        $source = $this->validateData($input, 'source', 'rb');
        $destination = $this->validateData($input, 'destination', 'wb+');

        // setup the query
        $where = $this->askChoiceQuestion(
            $input,
            $output,
            'Please select a field to query: ',
            $source->getHeaders(),
            'Condition invalid, please try again'
        );
        $condition = $this->askChoiceQuestion(
            $input,
            $output,
            'Please select the condition you\'d like applied to the field (leave blank for equals): ',
            Validation::CONDITIONS,
            'Condition invalid, please try again',
            0
        );
        $value = $this->askQuestion($input, $output, 'Please enter the value: ');

        $io->section('Processing, Standby');

        // run
        $query->from($source)
            ->select($input->getOption('select'))
            ->where($where)
            ->condition($condition)
            ->value($value)
            ->out($destination);
        if ($input->getOption('timer')) $query->timer();

        $results = $query->execute();

        $rows = (isset($results['data'])) ? $results['data']['rows'] : $results['rows'];
        $io->success([
            'Process Completed',
            "Rows : $rows",
            "Destination: {$input->getArgument('destination')}",
            (isset($results['timer'])) ? 'Time: '.round($results['timer']['elapsed'] / 1000, 2).' seconds' : ''
        ]);

        if (array_key_exists('timer', $results)) $output->writeln([

        ]);

    }
    /**
     * Validate data objects and return these from the source factory
     *
     * @param InputInterface    $input      The input interface for the console object
     * @param OutputInterface   $output     The output interface for the console object
     * @param string            $arg        The name of the argument passed to the command
     * @param string            $modifier   The modifier to use, i.e. r or w
     *
     * @return DataInterface
    **/
    private function validateData(InputInterface $input, string $arg, string $modifier)
    {
        $data = null;
        $arg = $input->getArgument($arg);
        if (stripos($modifier, 'w') !== false || file_exists($arg)) {
            switch (pathinfo($arg, PATHINFO_EXTENSION)) {
                case 'csv':
                    $data = DataSource::generate('file', 'csv');
                break;
                case 'xml':
                    list($node, $parent) = $this->getNodes($input);
                    $data = DataSource::generate('file', 'xml', $node, $parent);
                break;
                default:
                    throw new \InvalidArgumentException('Only CSV / XML files allowed');
            }
            $data->setSource($arg, ['modifier' => $modifier]);
            return $data;
        }
    }

    /**
     * If we're handling XML files then get these from the optional parameters
     * @param InputInterface    $input      The input interface for the console object
     *
     * @return array
    **/
    private function getNodes(InputInterface $input) : array
    {
        $node = $input->getOption('node');
        $parent = $input->getOption('parent');
        if (!$node || !$parent) {
            throw new InvalidArgumentException('Node and parent must be specified if you\'re using XML');
        }
        return [$node, $parent];
    }

    /**
     * Ask a question to the user and return the result as a string, an answer must be provided
     *
     * @param InputInterface    $input      The input interface for the console object
     * @param OutputInterface   $output     The output interface for the console object
     * @param string            $question   The question to ask
     *
     * @return $string
    **/
    private function askQuestion(InputInterface $input, OutputInterface $output, string $question) : string
    {
        $helper = $this->getHelper('question');
        $question = new Question($question);
        do {
            $answer =  $helper->ask($input, $output, $question);
        } while (!$answer);
        return $answer;

    }
    /**
     * Ask the user a multiple choice question
     *
     * @param InputInterface    $input          The input interface for the console object
     * @param OutputInterface   $output         The output interface for the console object
     * @param string            $question       The question to ask the user
     * @param array             $options        The possible options for the question
     * @param string            $errorMessage   The error message, should something go wrong
     * @param int               $default        The default option from the array, should nothing be provided
     *
     * @return mixed
    **/
    private function askChoiceQuestion(InputInterface $input, OutputInterface $output, string $question, array $options, string $errorMessage, int $default = null)
    {
        $helper = $this->getHelper('question');
        $c_question = new ChoiceQuestion($question, $options, $default);
        $c_question->setErrorMessage($errorMessage);
        do {
            $answer = $helper->ask($input, $output, $c_question);
        } while (!$answer);
        return $answer;

    }
}
