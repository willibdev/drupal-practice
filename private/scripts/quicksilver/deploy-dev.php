<?php

/**
 * @file
 * File to run after pantheon deployment.
 */

$pantheon_environment = $_ENV['PANTHEON_ENVIRONMENT'] ?? 'unknown';

$commands_by_environment = [
  'dev' => [
    'drush cr',
    'drush updb -y',
    'drush cim -y',
    'drush cr',
  ],
];

if (!isset($commands_by_environment[$pantheon_environment])) {
  echo "No commands defined for environment: $pantheon_environment\n";
  return;
}

foreach ($commands_by_environment[$pantheon_environment] as $command) {
  echo "Running: $command\n";

  $output = [];
  $status = 0;

  exec("$command 2>&1", $output, $status);

  echo implode("\n", $output) . "\n";

  if ($status !== 0) {
    throw new Exception("Command failed: $command");
  }
}

echo "✅ Quicksilver execution completed successfully\n";
