# About Information-Flow

> This is a repo for collecting information from rss and publishing to social platform such as wxofficial, telegram bot.

# Script List
1. wxofficial_sync
> sync information to wxofficial articles.

2. telegram_sync
> sync information to telegram bot.

### Benifits:
1. You can read your rss articles on local by html webpage.
2. You can read your rss articles on your platform.
3. You can sync rss at anytime by setting cron schedule.
4. You also can save html as image to share them.

# Future Plan.
> If there are someone use it.
1. Implement much more information sources, such as json api or web crawler.
2. More ai services.
3. More useful scripts.

# How to use.

> You can run this application in you local env or docker container.

1. Make your config file to normal by cloning from .template and set avaliable value.
2. Run it manually in php environment: php job.php [script you want to run]
*you can see the allowed parameters by -h.*
3. Run it in docker, but you should pay attention to correct api address in config.json when you request to host machine.
4. Also you can set running repeatly in cron.
