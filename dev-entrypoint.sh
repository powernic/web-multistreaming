#!/bin/sh

# copy from the image backup location to the volume mount
echo "Synchronizing vendor files..."
rsync -a /app/vendor/ /opt/cache/vendor/
echo "Synchronized vendor files"
exec php /app/worker.php
