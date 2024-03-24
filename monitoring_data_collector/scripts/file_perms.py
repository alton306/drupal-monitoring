#!/usr/bin/env python
import sys
import subprocess as sp
import vars

root_folder_path = sys.argv[1]
vars.initialize(root_folder_path)

if vars.drupal_boostraped_command.strip() == 'Successful':
    try:
        sp.run(
            '{} sudo find web -type d -exec chmod 755 {\} \\;'.format(vars.move_to_root_path_command),  shell=True, check=True, stdout=sp.PIPE).stdout.decode('utf-8')
        sp.run(
            '{} sudo find web -type f -exec chmod 644 \{\} \\;',  shell=True, check=True, stdout=sp.PIPE).stdout.decode('utf-8')
        sp.run('{} sudo chmod 444 {}/.htaccess;'.format(vars.move_to_root_path_command, vars.subpath),
                shell=True, check=True, stdout=sp.PIPE).stdout.decode('utf-8')
        sp.run('{} sudo chmod 644 {}/sites/default/settings.php;'.format(vars.move_to_root_path_command, vars.subpath),
                shell=True, check=True, stdout=sp.PIPE).stdout.decode('utf-8')
        sp.run('{} sudo chmod -R 775 {}/sites/default/files;'.format(vars.move_to_root_path_command, vars.subpath),
                shell=True, check=True, stdout=sp.PIPE).stdout.decode('utf-8')
    except Exception as exception:
        print('Error managing file permissions')
        print(exception)
        raise
