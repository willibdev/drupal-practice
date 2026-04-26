<?php

/**
 * @file
 * File to run after pantheon deployment.
 */

echo "Initilizing deployment update script... \n";

// Clear drush caches.
passthru('drush cr');
// Update drupal databse.
passthru('drush updb -y');
// Import database configurations.
passthru('drush cim -y');
// Clear drush cache again after updates and imports.
passthru('drush cr');

echo "Deployment update script completed successfully. \n";
