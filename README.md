DynamicEE
=========

ExpressionEngine 2.x fieldtype for configuring dynamic entries.


####SETUP
-------------
1. Copy files into `system/expressionengine/third_party/` and `themes/third_party/`
2. Create custom field with DynamicEE fieldtype



####TEMPLATE TAGS
-------------
DynamicEE returns a pipe-delimited list of `entry_id`'s based on its configuration, which is intended to be passed to the `fixed_order` param. [More info on fixed_order parameter](http://ellislab.com/expressionengine/user-guide/modules/channel/channel_entries.html#fixed-order)

Let's say your DynamicEE field shortname is `dynamicee_field_name`. Here are a couple usage scenarios.

```
{exp:channel:entries
    fixed_order="{dynamicee_field_name}"
}
    {title}
{/exp:channel:entries}
```

Use the response in a conditional statement before passing off to the channels tag. 

Note the "{}" wrapping the field name. This is important, as it will return the response as a string to be evaluated. 
```
{if "{dynamicee_field_name}" != ""}
    {exp:channel:entries
        fixed_order="{dynamicee_field_name}"
    }
        {title}
    {/exp:channel:entries}
{/if}
```

####FIELD INSTRUCTIONS
-------------
Here's a default "instructions" string to be used when you're setting up your custom DynamicEE fieldtype. 

> Channel and Category criteria are cumulative, meaning that if Channel A is selected, and Category B, C, and D are selected, then only entries in Channel A that are also in either Category B, C, or D will be displayed. Status and Time Range are also cumulative, so that entries found by the Channel and Category parameters will be limited by the Status and Time Range selections.
