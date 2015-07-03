<?php
namespace Meanbee\Magerun\Dac\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CopyCommand extends AbstractMagentoCommand
{

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
                "source", null,
                InputOption::VALUE_REQUIRED,
                "Use an alternate theme to copy the file from (specified in <package>[/<theme>] format)."
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
            $input->getArgument("filepath")
        );
        $destination = $this->getDesignPath(
            $input->getArgument("destination"),
            $input->getArgument("filepath")
        );
        $destination_dir = dirname($destination);

        $output->writeln(sprintf(
            "<info>Copy file:</info>\n\t<comment>Source: %s</comment>\n\t<comment>Destination: %s</comment>",
            $source,
            $destination
        ));

        if (!file_exists($source)) {
            $output->writeln("<error>Source file does not exist!</error>");
            return 1;
        }

        if (file_exists($destination) && !$input->getOption("force")) {
            $output->writeln("<error>Destination file already exists!</error>");
            return 1;
        }

        if (!file_exists($destination_dir)) {
            if (!mkdir($destination_dir, 0755, true)) {
                $output->writeln("<error>Failed to create the destination directory!</error>");
                return 1;
            }
        } else if (!is_dir($destination_dir)) {
            $output->writeln("<error>Destination is not a directory!</error>");
            return 1;
        }

        if (!copy($source, $destination)) {
            $output->writeln("<error>Failed!</error>");
            return 1;
        }

        $output->writeln("<info>Done</info>");

        return 0;
    }

    /**
     * Get the path to the specified theme's design folder
     * or a specific file inside it.
     *
     * @param string      $theme
     * @param string|null $file
     *
     * @return string
     */
    protected function getDesignPath($theme, $file = null)
    {
        $theme = explode("/", $theme);

        $path = array(
            $this->getApplication()->getMagentoRootFolder(),
            "app",
            "design",
            "frontend",
            !empty($theme[0]) ? $theme[0] : "base",
            !empty($theme[1]) ? $theme[1] : "default"
        );

        if ($file) {
            $path[] = $file;
        }

        return implode(DIRECTORY_SEPARATOR, $path);
    }
}
