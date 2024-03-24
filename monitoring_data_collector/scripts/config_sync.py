#!/usr/bin/env python
import sys
import subprocess as sp
import vars
import datetime

root_folder_path = sys.argv[1]
user = 'ryan'

# timestamp for the backup
current_time = datetime.datetime.now()
date = current_time.strftime("%d-%m-%Y")

vars.initialize(root_folder_path)

# checks for config changes if none exist will return nothing
is_config_changes = sp.run('{} drush cr && drush config:status'.format(
    vars.move_to_root_path_command), shell=True, check=False, stdout=sp.PIPE).stdout.decode('utf-8')

# if the is_config_changes is empty it will return as false
if(bool(is_config_changes)):
    try:
        branch_name = 'config_sync_changes_{}_{}'.format(user, date)
        # makes sure git branch has no extra changes and is up to date before branching out
        sp.run('{} git reset --hard'.format(vars.move_to_root_path_command), shell=True,
               check=True, stdout=sp.PIPE).stdout.decode('utf-8')

        # creates new branch names if after the user who ran the function along with the date
        sp.run('{} git check -b {}'.format(vars.move_to_root_path_command,
               branch_name), shell=True, check=True, stdout=sp.PIPE).stdout.decode('utf-8')

        # clears drupal cache and run config export
        sp.run('{} drush cr && drush cex -y && drush cr'.format(
            vars.move_to_root_path_command), shell=True, check=False)

        # adds config to commit
        sp.run('{} git add config'.format(
            vars.move_to_root_path_command), shell=True, check=False)

        # commits changes to branch
        sp.run('{} git commit -m \'reporting tool config import User: {} Date: {}\''.format(
            vars.move_to_root_path_command, user, date), shell=True, check=False)

        #pushes branch to repo
        sp.run('{} git push --set-upstream origin {}'.format(vars.move_to_root_path_command,branch_name),
               shell=True, check=False)

        print('Branch has been pushed to repo to complete the process please create a pull request in bitbucket')

    except Exception as exception:
        print(exception)
        raise
else:
    print('no changes found')
