# Magerun Duplicate and Commit plugin

Duplicate Magento files from one theme to another and commit them to git.

## About

Building client sites always requires customising default template and skin files. To make these
customisations easier to see from git history, we usually copy the file into the client theme and
commit it without any modifications first. This Magerun plugin adds a command to do that.

## Installation

To install this plugin, clone this repository in `~/.n98-magerun/modules`:

    mkdir -p ~/.n98-magerun/modules
    cd ~/.n98-magerun/modules
    git clone https://github.com/meanbee/magerun-dac.git

## Usage

    n98-magerun.phar dev:theme:copy [-f|--force] [-c|--commit] [-m|--commit-message="..."] [--source="..."] [--adminhtml] [--skin] filepath destination
    
    Arguments:
     filepath              Path to the file to copy, relative to the theme directory in design.
     destination           Destination theme (specified in <package>[/<theme>] format).
    
    Options:
     --force (-f)          Overwrite the destination file if it already exists.
     --commit (-c)         Commit the resulting file to git.
     --commit-message (-m) Specify the commit message used with --commit. (default: "Initial commit of %s")
     --source              Use an alternate theme to copy the file from (specified in <package>[/<theme>] format).
     --adminhtml           Use adminhtml themes for source and destination.
     --skin                Copy the file between the theme skin folders.

By default the command copies the specified file from "app/design/frontend/base/default" to your specified theme. You can
specify `--adminhtml` to copy a file from "default/default" adminhtml theme to your custom adminhtml theme or specify
`--skin` to copy files between frontend or adminhtml themes in the "skin" folder.

You can use a different theme to copy the file from with the `--source` option. Themes are specified in
`<package>/<theme>` notation. You can omit the `/<theme>` part to use the "default" theme.

To commit the copied file to git, use the `--commit` option. You can specify a custom commit message with
`--commit-message`. If the specified commit message contains "%s", it will be replaced with the resulting file path,
relative to the Magento root directory.

The command will stop if the destination file already exists. To overwrite existing files, use the `--force` option.
