<?php
namespace Meanbee\Magerun\Dac\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CopyCommand extends AbstractMagentoCommand
{
    const SUCCESS = 0;
    const ERROR_SOURCE_DOES_NOT_EXIST = 1;
    const ERROR_DESTINATION_EXISTS = 2;
    const ERROR_FAILED_TO_CREATE_DIRECTORY = 3;
    const ERROR_DESTINATION_NOT_DIRECTORY = 4;
    const ERROR_COPY_FAILED = 5;

    const DEFAULT_COMMIT_MESSAGE = "Initial commit of %s";

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        $this
            ->setName("dev:theme:copy")
            ->setDescription("Copy a file from the \"base/default\" design theme to the specified theme.")
            ->addArgument(
                "filepath",
                InputArgument::REQUIRED,
                "Path to the file to copy, relative to the theme directory in design."
            )
            ->addArgument(
                "destination",
                InputArgument::REQUIRED,
                "Destination theme (specified in <package>[/<theme>] format)."
            )
            ->addOption(
                "force", "f",
                InputOption::VALUE_NONE,
                "Overwrite the destination file if it already exists."
            )
            ->addOption(
                "commit", "c",
                InputOption::VALUE_NONE,
                "Commit the resulting file to git."
            )
            ->addOption(
                "commit-message", "m",
                InputOption::VALUE_REQUIRED,
                "Specify the commit message used with --commit.",
                static::DEFAULT_COMMIT_MESSAGE
            )
            ->addOption(
                "source", null,
                InputOption::VALUE_REQUIRED,
                "Use an alternate theme to copy the file from (specified in <package>[/<theme>] format)."
            )
            ->addOption(
                "adminhtml", null,
                InputOption::VALUE_NONE,
                "Use adminhtml themes for source and destination."
            )
            ->addOption(
                "skin", null,
                InputOption::VALUE_NONE,
                "Copy the file between the theme skin folders."
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);

        $source = $this->getDesignPath(
            $input->getOption("source"),
            $input->getArgument("filepath"),
            $input->getOption("skin"),
            $input->getOption("adminhtml")
        );
        $destination = $this->getDesignPath(
            $input->getArgument("destination"),
            $input->getArgument("filepath"),
            $input->getOption("skin"),
            $input->getOption("adminhtml")
        );

        $output->writeln(sprintf(
            "<info>Copy file:</info>\n\t<comment>Source: %s</comment>\n\t<comment>Destination: %s</comment>",
            $source,
            $destination
        ));

        $result = $this->copy($source, $destination, $input->getOption("force"));

        if ($result !== static::SUCCESS) {
            switch ($result) {
                case static::ERROR_SOURCE_DOES_NOT_EXIST:
                    $message = "Source file does not exist!";
                    break;
                case static::ERROR_DESTINATION_EXISTS:
                    $message = "Destination file already exists!";
                    break;
                case static::ERROR_FAILED_TO_CREATE_DIRECTORY:
                    $message = "Failed to create the destination directory!";
                    break;
                case static::ERROR_DESTINATION_NOT_DIRECTORY:
                    $message = "Destination is not a directory!";
                    break;
                case static::ERROR_COPY_FAILED:
                default:
                    $message = "Failed!";
                    break;
            }

            $output->writeln(sprintf("<error>%s</error>", $message));
            return 1;
        }

        if ($input->getOption("commit")) {
            if (!$this->commit($destination, $input->getOption("commit-message"))) {
                $output->writeln("<error>Failed to commit the resulting file to git!</error>");
                return 2;
            }
        }

        $output->writeln("<info>Done</info>");

        return 0;
    }

    /**
     * Copy a file from source to destination. Create the destination
     * directory structure if it does not exist.
     *
     * @param      $source
     * @param      $destination
     * @param bool $force Overwrite the destination if it exists
     *
     * @return int
     */
    protected function copy($source, $destination, $force = false)
    {
        $destination_dir = dirname($destination);

        if (!file_exists($source)) {
            return static::ERROR_SOURCE_DOES_NOT_EXIST;
        }

        if (file_exists($destination) && !$force) {
            return static::ERROR_DESTINATION_EXISTS;
        }

        if (!file_exists($destination_dir)) {
            if (!mkdir($destination_dir, 0755, true)) {
                return static::ERROR_FAILED_TO_CREATE_DIRECTORY;
            }
        } else if (!is_dir($destination_dir)) {
            return static::ERROR_DESTINATION_NOT_DIRECTORY;
        }

        if (!copy($source, $destination)) {
            return static::ERROR_COPY_FAILED;
        }

        return static::SUCCESS;
    }

    /**
     * Commit the specified file to git with the given message.
     *
     * @param string $file
     * @param string $message
     *
     * @return bool
     */
    protected function commit($file, $message = null)
    {
        system(sprintf('git add %s', $file), $status);

        if ($status === 0) {
            $message = sprintf(
                $message ?: static::DEFAULT_COMMIT_MESSAGE,
                str_replace($this->getApplication()->getMagentoRootFolder() . DIRECTORY_SEPARATOR, "", $file)
            );

            system(sprintf('git commit -m "%s"', addslashes($message)), $status);
        }

        return ($status === 0);
    }

    /**
     * Get the path to the specified theme's design folder
     * or a specific file inside it.
     *
     * @param string      $theme
     * @param string|null $file
     * @param bool        $is_skin
     * @param bool        $is_adminhtml
     *
     * @return string
     */
    protected function getDesignPath($theme, $file = null, $is_skin = false, $is_adminhtml = false)
    {
        @list($package, $theme) = explode("/", $theme);

        $path = array(
            $this->getApplication()->getMagentoRootFolder(),
            $is_skin ? "skin" : "app" . DIRECTORY_SEPARATOR . "design",
            $is_adminhtml ? "adminhtml" : "frontend",
            $package ?: ($is_adminhtml ? "default" : "base"),
            $theme ?: "default"
        );

        if ($file) {
            $path[] = $file;
        }

        return implode(DIRECTORY_SEPARATOR, $path);
    }
}
