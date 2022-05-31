<?php
/**
 * Plugin Name: Wikimedia Contest
 * Description: Manage the Wikimedia Contests
 * Version:     1.0.0
 * Author:      Human Made
 * Author URI:  https://github.com/humanmade
 *
 * @package wikimedia-contest
 * @version 1.0.0
 */

namespace WikimediaContest;

require_once __DIR__ . '/inc/post-type.php';
require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/blocks/audio-submission-form/audio-form.php';

bootstrap();
