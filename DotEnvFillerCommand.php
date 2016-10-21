<?php

namespace KDuma\DotEnvFiller;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DotEnvFillerCommand.
 */
class DotEnvFillerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'config:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create/fill missing fields in `.env` file based on rules in `.env.example` file.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $OUTPUT = [];
        $CHANGES = [];

        $fileUser = base_path('.env');
        if (file_exists($fileUser)) {
            $autodetect = ini_get('auto_detect_line_endings');
            ini_set('auto_detect_line_endings', '1');
            $lines = file($fileUser, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            ini_set('auto_detect_line_endings', $autodetect);
            foreach ($lines as $line) {
                $line = explode('=', $line, 2);
                if (count($line) == 2) {
                    $OUTPUT[$line[0]] = $line[1];
                }
            }
        }

        $file = base_path('.env.example');
        if (! file_exists($file)) {
            $this->error('.env.example don\'t exists');

            return;
        }
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);


        $values = [];
        foreach ($lines as $line) {
            $line = explode('=', $line, 2);
            if (count($line) == 2) {
                $values[$line[0]] = $line[1];
            }
        }

        foreach ($values as $key => $value) {
            if (preg_match('/^\\(([^\\)]+)\\)({([^\\}]+)})?$/us', $value, $matches)) {
                if (! isset($OUTPUT[$key]) || $this->option('overwrite') && $this->confirm('Do you want to overwrite existing value of '.strtoupper($key).' = '.$OUTPUT[$key].'?', false)) {
                    $choice = '';
                    if (isset($matches[3]) && $matches[3] != '') {
                        $defaults = explode('|', $matches[3]);
                        $default = null;
                        foreach ($defaults as $d) {
                            $rule = [];
                            $tmp = explode(':', $d);
                            $rule['value'] = $tmp[1];
                            $tmp = explode('=', $tmp[0]);
                            $rule['var'] = $tmp[0];
                            $rule['is'] = $tmp[1];

                            if ($OUTPUT[$rule['var']] == $rule['is']) {
                                $default = $rule['value'];
                            }
                        }
                        if (! is_null($default)) {
                            if (! $this->option('defaults') || $this->confirm('Do you want to use default value for '.strtoupper($key).' = '.$default.'?', true)) {
                                $choice = $default;
                                $OUTPUT[$key] = $choice;
                                $CHANGES[$key] = $choice;
                            }
                        }
                    }
                    if ($choice == '') {
                        $choices = explode('|', $matches[1]);
                        $choices = array_combine(range(1, count($choices)), array_values($choices));
                        if (count($choices) == 1) {
                            $choice = $choices[1];
                        } else {
                            $choice = $this->choice($key, $choices);
                        }
                        switch ($choice) {
                            case 'PASSWORD':
                                $choice = $this->secret('Enter value for '.strtoupper($key).':');
                                break;
                            case 'TEXT':
                                $choice = $this->ask('Enter value for '.strtoupper($key).':');
                                break;
                        }
                        $OUTPUT[$key] = $choice;
                        $CHANGES[$key] = $choice;
                    }
                }
            } else {
                if (! isset($OUTPUT[$key])) {
                    $OUTPUT[$key] = $value;
                    $CHANGES[$key] = $value;
                }
            }
        }

        if (count($CHANGES) > 0) {
            $GROUPS = [];

            foreach ($OUTPUT as $key => $val) {
                $tmp = explode('_', $key);
                if (count($tmp) == 1) {
                    $GROUPS['OTHER'][$key] = $val;
                } else {
                    $GROUPS[$tmp[0]][$key] = $val;
                }
            }
            $TABLE = [];
            $OUTPUT = [];
            foreach ($GROUPS as $name => $GROUP) {
                $o = [];
                $o[] = '# '.$name;
                foreach ($GROUP as $key => $val) {
                    $o[] = $key.'='.$val;
                    $TABLE[] = [$name, $key, $val];
                }
                $OUTPUT[] = implode(PHP_EOL, $o);
            }
            $OUTPUT = implode("\n\n", $OUTPUT);

            $this->info($OUTPUT);
            // DEBUG: $this->table(['Group','Key','Value'],$TABLE);
            if ($this->confirm('Do you want to write above content to .env file?', true)) {
                file_put_contents($fileUser, $OUTPUT);
                $this->info('File written.');
            }
        } else {
            $this->info('There is nothing to write.');
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['overwrite', 'o', InputOption::VALUE_NONE, 'Don\'t skip keys that exists in `.env`. (will ask if you want to overwrite or not)', null],
            ['defaults', 'd', InputOption::VALUE_NONE, 'Ask for defaults. (if you don\'t use this option command will assume that you want defaults options)', null],
        ];
    }
}
