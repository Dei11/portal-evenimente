<?php

/**
 * Plugin Name: WP Event Manager Calendar
 * Description: A custom calendar plugin integrated with WP Event Manager using Tui.Calendar.
 * Version: 1.3
 * Author: Andrei Bogdan 
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue Tui.Calendar scripts and styles
function wemc_enqueue_scripts()
{
    wp_enqueue_style('tui-calendar-css', 'https://uicdn.toast.com/calendar/latest/toastui-calendar.min.css');
    wp_enqueue_script('tui-calendar-js', 'https://uicdn.toast.com/calendar/latest/toastui-calendar.min.js', array('jquery'), null, true);
    wp_localize_script('tui-calendar-js', 'wemc_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'wemc_enqueue_scripts');

// Shortcode to display the calendar
function wemc_display_calendar()
{
    return '<div id="wemc-calendar" style="height: 600px;"></div>';
}
add_shortcode('event_calendar', 'wemc_display_calendar');

// Fetch events from WP Event Manager
function wemc_get_events()
{
    $args = array(
        'post_type' => 'event_listing',
        'posts_per_page' => -1
    );

    $query = new WP_Query($args);
    $events = array();

    while ($query->have_posts()) {
        $query->the_post();
        $start_date = get_post_meta(get_the_ID(), '_event_start_date', true);

        if (!empty($start_date)) {
            $events[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'start' => $start_date,
                'url' => get_permalink()
            );
        }
    }

    wp_reset_postdata();
    wp_send_json($events);
}
add_action('wp_ajax_wemc_get_events', 'wemc_get_events');
add_action('wp_ajax_nopriv_wemc_get_events', 'wemc_get_events');

// Inline JavaScript for Tui.Calendar
add_action('wp_footer', function () {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var calendarEl = document.getElementById("wemc-calendar");
            if (!calendarEl) return;

            var calendar = new tui.Calendar(calendarEl, {
                defaultView: "month",
                taskView: false,
                scheduleView: ["time"],
                useCreationPopup: false,
                useDetailPopup: true,
                template: {
                    time(event) {
                        return `<strong>${event.title}</strong>`;
                    }
                }
            });
            
            fetch(wemc_ajax.ajax_url + "?action=wemc_get_events")
                .then(response => response.json())
                .then(events => {
                    events.forEach(event => {
                        calendar.createEvents([
                            {
                                id: event.id,
                                calendarId: "1",
                                title: event.title,
                                start: event.start,
                                end: event.start,
                                category: "time",
                                isAllDay: true,
                                location: event.url
                            }
                        ]);
                    });
                });
        });
    </script>';
});

// Inline CSS for Calendar
add_action('wp_head', function () {
    echo '<style>
        #wemc-calendar {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>';
});
