# Commands

The KCore includes a set of commands that can be run from the command line
interface as some utilizy controls and operations.

### Core Optimize
The command `kcore:optimize` optimizes all the Solr cores defined.

Example:

The following command will optimize only the `public` core on the KCore instance.
`php app/console kcore:optimize --core=public`