# Commands

The KCore includes a set of commands that can be run from the command line
interface as some utility checks and operations.

### Core Optimize
The command `kcore:optimize` optimizes all the available Solr cores.

Example:
> `php app/console kcore:optimize`

The following command will optimize only the `public` core on the KCore instance.
> `php app/console kcore:optimize --core=public`


## Thumbnails

### Generate next thumbnail
The command `kcore:thumbnails:generate` generates the next Thumbnail from the queue.

This command should be included in a Cron job and scheduled to run every 2 minutes.

### Clean thumbnails folders
The command `kcore:thumbnails:clean` will clean the thumbnail folders from old and
not required files

This command should be included in a Cron job and scheduled for a daily run.
