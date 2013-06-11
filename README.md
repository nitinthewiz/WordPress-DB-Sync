WordPress-DB-Sync
=================

A plugin to sync your WordPress posts with Dropbox as MarkDown files.

Right now, if you add it to your WP installation, it'll convert the your posts (published and drafts) 
to .md files and save to a local folder.

Next steps - 
1. oAuth with Dropbox (which I know nothing about)
2. Save to Dropbox instead of locally and sync in case the files already exist. Hopefully DB SDK 
will have some way to check files for changes. If not, original code will need to be written for this.
3. A settings page for the oAuth process. I've started work on that, but it's a huge learning curve. 
Good thing I'm interested in learning.
4. Write code to sync backwards, so that we can get our data back. For this, the code I wrote for ADNPages will 
come in handy.

Stretch Goals - 
1. Sync all posts, pages, other types of files.
