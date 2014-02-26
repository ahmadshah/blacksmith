<?php namespace Delegates;

use Console\GenerateCommand;
use Configuration\ConfigReader;
use Generators\Generator;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Mustache_Engine;

/**
 * Class for delegation of application operation related
 * to executing a generation request and seeing that it is 
 * completed properly
 */
class GeneratorDelegate implements SingleGeneratorDelegateInterface
{

    /**
     * Command that delegated the request
     * 
     * @var Console\GenerateCommand
     */
    protected $command;

    /**
     * Configuration of generation
     * 
     * @var Configuration\ConfigReader
     */
    protected $config;

    /**
     * Generator to perform the code generation
     * 
     * @var Generators\Generator
     */
    protected $generator;

    /**
     * String containing the requested generation action
     * 
     * @var string
     */
    protected $generation_request;

    /**
     * String containing the entity name the 
     * requested generation should use
     * 
     * @var string
     */
    protected $generate_for_entity;

    /**
     * Constructor to setup up our class variables
     * 
     * @param GenerateCommand $cmd          executed command
     * @param ConfigReader    $cfg          reader of the config file
     * @param Generator       $gen          generator to run
     * @param array           $command_args command arguments
     */
    public function __construct(GenerateCommand $cmd, ConfigReader $cfg, Generator $gen, array $command_args)
    {
        $this->command             = $cmd;
        $this->config              = $cfg;
        $this->generator           = $gen;
        $this->generate_for_entity = $command_args['entity'];
        $this->generation_request  = $command_args['what'];
    }


    /**
     * Function to run the delegated operations and return boolean
     * status of result.  If false the command should comment out
     * the reasons for the failure.
     * 
     * @return bool success / failure of operations
     */
    public function run()
    {
        //check if the loaded config is valid
        if (! $this->config->validateConfig()) {
            $this->command->comment(
                'Error',
                'The loaded configuration file is invalid',
                true
            );
            return false;
        }

        //get possible generations
        $possible_generations = $this->config->getAvailableGenerators(
            $this->config->getConfigType()
        );

        //see if passed in command is one that is available
        if (! in_array($this->generation_request, $possible_generations)) {
            $this->command->comment(
                'Error',
                "{$this->generation_request} is not a valid option",
                true
            );

            $this->command->comment(
                'Error Details',
                "Please choose from: ". implode(", ", $possible_generations),
                true
            );
            return false;
        }

        //should be good to generate, get the config values
        $settings  = $config->getConfigValue($this->generation_request);

        $template  = $settings[ConfigReader::CONFIG_VAL_TEMPLATE];
        $directory = $settings[ConfigReader::CONFIG_VAL_DIRECTORY];
        $filename  = $settings[ConfigReader::CONFIG_VAL_FILENAME];

        //run generator
        $this->generator->make(
            $this->generate_for_entity,
            $template,
            $directory,
            $filename
        );

        return true;
    }
}