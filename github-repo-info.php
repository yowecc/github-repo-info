<?php
/*
Plugin Name: GitHub Repository Info Display
Plugin URI: https://yowe.cc
Description: A simple plugin to display GitHub repository info and recent commits.
Version: 1.0
Author: clrsdream
Author URI: https://www.clrs.me
License: GPLv2 or later
Text Domain: github-repo-info
*/

// 阻止直接访问该文件
if (!defined('ABSPATH')) {
    exit;
}

// 包含核心功能代码
include(plugin_dir_path(__FILE__) . 'includes/github-repo-functions.php');

// 注册短代码 [github_repo_info]，用于在页面或文章中展示 GitHub 仓库信息
function register_github_repo_shortcode() {
    add_shortcode('github_repo', 'display_github_repo_info_shortcode');
}

add_action('init', 'register_github_repo_shortcode');

// 在前台加载 CSS 文件
function github_repo_info_enqueue_styles() {
    wp_enqueue_style('github-repo-style', plugins_url('/css/style2.css', __FILE__),array(),
    '2.0.5',  // 使用当前时间作为版本号，确保样式文件不会被缓存
    'all');
}
add_action('wp_enqueue_scripts', 'github_repo_info_enqueue_styles');
