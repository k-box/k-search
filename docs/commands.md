# Commands

The K-Search includes a set of commands that can be run from the command line
interface as some utility checks and operations.

### Core Optimize
The command `k-search:optimize` optimizes all the available Solr cores.

Example:
> `php bin/console k-search:optimize`

The following command will optimize only the `public` core on the K-Search instance.
> `php bin/console k-search:optimize --core=public`


## Thumbnails

### Generate next thumbnail
The command `k-search:thumbnails:generate` generates the next Thumbnail from the queue.

This command should be included in a Cron job and scheduled to run every 2 minutes.

### Clean thumbnails folders
The command `k-search:thumbnails:clean` will clean the thumbnail folders from old and
not required files

This command should be included in a Cron job and scheduled for a daily run.
