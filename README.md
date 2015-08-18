# Flood

Flood module for CosCMS

# Example

In the `flood.ini` you can list how you want your flooding method to react. 
E.g. you allow 3 comments in every 60 minutes on the action `comment_create`

    flood_comment_create[post_max] = 3
    flood_comment_create[post_interval] = 3600

Then you increment the `comment_create` value in the flood table like this: 

    use modules\flood\module as flood

    flood::performFloodCheck('comment_create');

When the limit is reached, you will be directed to a page telling you, when
you can e.g. post again.  

It will only work on `logged in users`
