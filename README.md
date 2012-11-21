### About

Flood module for CosCMS

Module used for checking if users are flooding your application. So far it only
checks on verified users. You could do the flood check by adding an 
event::triggerEvent in your own module. 

### Example

In this example the flood::events must be listed in the comment.ini file in 
the following way: 

    comment_events[] = 'flood::events'

This is how it is used in the module comment in the method 
`comment::createComment();` 

    $args = array ('action' => 'comment_create');
    $res = event::getTriggerEvent(
        get_module_ini('comment_events'), $args
    );

In the flood.ini you can list how you want your flooding method to react. 
E.g. you allow 3 comments in every 60 minuts on the action `comment_create`

    flood_comment_create[post_max] = 3
    flood_comment_create[post_interval] = 3600

If you want to trigger something on e.g. account::create you would use 

    flood_account_create[post_max] = 3
    flood_account_create[post_interval] = 3600

Note the word `flood_` and then the action in questions: e.g. `account_create` 
