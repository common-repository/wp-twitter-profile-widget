<?php

// Load the twitter API PHP by J7mbo/twitter-api-php
require_once('TwitterAPIExchange.php');
// Set the access tokens
$settings = array(
    'oauth_access_token' => $access_token,
    'oauth_access_token_secret' => $access_token_secret,
    'consumer_key' => $consumer_key,
    'consumer_secret' => $consumer_key_secret
);

// Set the target to false if no value is present.
// This is to prevent debug warnings.
if (!isset($twitter_target))
{
    $twitter_target = false;
}
if (!isset($twitter_show_bio))
{
    $twitter_show_bio = false;
}
if (!isset($twitter_show_counts))
{
    $twitter_show_counts = false;
}
if (!isset($twitter_show_follow))
{
    $twitter_show_follow = false;
}

// Create an atts array, used to sort cache files
$atts = array(
    'settings' => $settings,
    'id' => $twitter_id,
    'count' => $twitter_count,
    'target' => $twitter_target,
    'timeout' => $twitter_timeout,
    'show_bio' => $twitter_show_bio,
    'show_stats' => $twitter_show_counts
);

// Set the API url and request method
$url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
$requestMethod = "GET";

// if $twitter_target is blank set $target
if ($twitter_target == 'on' || $twitter_target == true)
{
    $target = ' target="_blank" ';
}
else
{
    $target = '';
}

// Check if $twitter_count is a number, if not set it to 3
if (!is_numeric($twitter_count))
{
    $twitter_count = 3;
}

// Set the get field to call the twitter username and the number of tweets to retrieve
$getfield = "?screen_name=$twitter_id&count=$twitter_count";
// Create a new instance of TwitterAPIExchange
$twitter = new TwitterAPIExchange($settings);
// Store the decoded reply
$string = json_decode($twitter->setGetfield($getfield)
->buildOauth($url, $requestMethod)
->performRequest(),$assoc = TRUE);

// If there was an error display a message
if(array_key_exists('errors', $string) && $string["errors"][0]["message"] != "")
{
    $output = "<h3>Sorry, there was a problem.</h3><p>Twitter returned the following error message:</p><p><em>".$string['errors'][0]["message"]."</em></p>";exit();
}
else
{
    // There is no error message, attempt to create the widget

    // From now on the output will be cached using transient
    // Set the cache name and prefix
    $cache = get_transient('f13twitter' . md5(serialize($atts)));

    if ($cache)
    {
        // If the cache exists, return it rather than re-creating it
        $output = $cache;
    }
    else
    {

        // create the widget output
        $output = '
        <div class="twitter-widget-container">
        <div class="twitter-widget-head-bar">
        <img src="' . $string[0]['user']['profile_banner_url'] . '" class="twitter-banner" />
        <a ' . $target . ' href="https://twitter.com/' . $string[0]['user']['screen_name'] . '" class="twitter-names_link">
        <img src="' . str_replace('normal', '400x400', $string[0]['user']['profile_image_url_https']) . '" class="twitter-widget-profile-image"/>
        <span class="twitter-names">
        <p class="twitter-name">' .
        $string[0]['user']['name'] . '
        </p>
        <span class="twitter-username">
        @' . $string[0]['user']['screen_name'] . '
        </span>
        </span>
        </a>
        </div>
        <div class="twitter-widget-content">



        <br style="clear: both;" />';
        if ($twitter_show_bio == "on")
        {
            $output .= '<div class="twitter-description">' .
            getLinksFromTwitterText($string[0]['user']['description'], $twitter_target) . '
            </div>';
        }
        if ($twitter_show_follow == "on")
        {
            $output .= '<a ' . $target . 'class="twitter-follow-button" href="https://twitter.com/intent/follow?screen_name=' . $string[0]['user']['screen_name'] . '" data-size="large" data-width="960" data-height="600"> Follow @' . $string[0]['user']['screen_name'] . '</a>
            <br style="clear: both;" />';
        }
        // Check if twitter counts are to be shown
        if ($twitter_show_counts == "on")
        {
            $output .= '
            <a ' . $target . ' href="https://twitter.com/' . $string[0]['user']['screen_name'] .  '" ' . $target . ' class="twitter-widget-profile-link">
                <div class="twitter-widget-links">
                    <div class="twitter-widget-links-head">
                        Tweets
                    </div>
                    <div class="twitter-widget-links-numbers">' .
                        $string[0]['user']['statuses_count'] . '
                    </div>
                </div>
            </a>
            <a ' . $target . ' href="https://twitter.com/' . $string[0]['user']['screen_name'] .  '/following" ' . $target . ' class="twitter-widget-profile-link">
                <div class="twitter-widget-links">
                    <div class="twitter-widget-links-head">
                        Following
                    </div>
                    <div class="twitter-widget-links-numbers">' .
                        $string[0]['user']['friends_count'] . '
                    </div>
                </div>
            </a>
            <a ' . $target . ' href="https://twitter.com/' . $string[0]['user']['screen_name'] .  '/followers" ' . $target . ' class="twitter-widget-profile-link">
                <div class="twitter-widget-links">
                    <div class="twitter-widget-links-head">
                        Followers
                    </div>
                    <div class="twitter-widget-links-numbers">' .
                        $string[0]['user']['followers_count'] . '
                    </div>
                </div>
            </a>
            <br style="clear: both;" />';
        }
        $output .= '</div>';

        if ($twitter_count != 0)
        {
            $output .= '
            <div class="twitter-widget-tweets-header">
            Recent tweets
            </div>';

            foreach($string as $items)
            {

                $created_at = explode(" ", $items['created_at']);
                $created_at_time = explode(":", $created_at[3]);
                $created_at_string = $created_at_time[0] . ':' . $created_at_time[1] . ' ';
                $created_at_string .= ' - ';
                $created_at_string .= $created_at[2] . ' ' . $created_at[1] . ' ' . $created_at[5];

                $output .= '
                <div class="tweet">
                <div class="tweet-content">' .
                //$items['text'] . '
                getLinksFromTwitterText($items['text'], $twitter_target);
                if (array_key_exists('media', $items['entities']))
                {
                    $output .= '<a href="' . $items['entities']['media'][0]['url'] . '" ' . $target . ' class="tweet-link" />';
                    if ($items['entities']['media'][0]['media_url'] != '')
                    {
                        $output .= '
                        <img src="' . $items['entities']['media'][0]['media_url'] . '" class="tweet-media" />';
                    }
                }
                $output .= '
                </div>
                <div class="tweet-time">' .
                $created_at_string . '
                </div>
                </a>
                </div>';


            }
        }
        $output .= '</div>';

        // Get the timout in seconds
        $timeout = $twitter_timeout * 60;

        // If the timeout is 0, set it to 1 second
        if ($timeout == 0)
        {
            $timeout = 1;
        }

        // Store the output into the cache
        set_transient('f13twitter' . md5(serialize($atts)), $output, $timeout);

    }

    echo $output;
}

function getLinksFromTwitterText($string, $target)
{
    $string = preg_replace('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', '<a href="$0" " title="$0">$0</a>', $string);
    // Converts hashtags to a link
    $string = preg_replace("/#([A-Za-z0-9\/\.]*)/", "<a href=\"http://twitter.com/search?q=$1\" >#$1</a>", $string);
    // Converts @user to a link
    $string = preg_replace("/@([A-Za-z0-9\/\.]*)/", "<a href=\"http://www.twitter.com/$1\" >@$1</a>", $string);

    // If target blank is set to on, add the target to the link
    if ($target == 'on' || $target == true)
    {
        $string = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $string);
    }

    return $string;
}
?>
