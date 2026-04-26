<?php

/**
 * @file
 * File to run after pantheon deployment.
 */

// Run Drush commands after deploy.
$commands = [
  'drush cr',
  'drush updb -y',
  'drush cim -y',
  'drush cr',
];

foreach ($commands as $command) {
  echo "Running: $command\n";
  passthru($command, $status);

  if ($status !== 0) {
    throw new Exception("Command failed: $command");
  }
}
