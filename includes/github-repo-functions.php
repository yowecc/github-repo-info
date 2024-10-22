<?php

// 函数：获取并展示 GitHub 仓库信息（带缓存功能）
function display_github_repo_info_shortcode($atts) {
    // 解析短代码的属性
    $atts = shortcode_atts(array(
        'username' => 'clrsdream', // 默认 GitHub 用户名
        'repo' => 'Hello-World'  // 默认仓库名
    ), $atts, 'github_repo_info');

    // 调用核心函数来展示 GitHub 仓库信息
    return display_github_repo_info($atts['username'], $atts['repo']);
}

// 核心功能函数（与之前的实现一致）
function display_github_repo_info($username, $repo) {
    // 使用 transient 来缓存仓库信息，缓存时间为 1 小时
    $cache_key = "github_repo_info_{$username}_{$repo}";
    $cached_data = get_transient($cache_key);

    if ($cached_data !== false) {
        // 如果有缓存数据，直接使用缓存数据
        $data = $cached_data;
    } else {
        // 如果没有缓存数据，则从 GitHub API 获取数据
        $api_url = "https://api.github.com/repos/$username/$repo";
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'User-Agent' => 'WordPress' // GitHub API 要求设置 User-Agent
            )
        ));

        // 检查是否请求失败
        if (is_wp_error($response)) {
            return "无法获取仓库信息。";
        }

        // 获取响应数据
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // 设置缓存，缓存时间为 1 小时
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    }

    if (empty($data)) {
        return "仓库信息为空。";
    }

    if (isset($data->license)) {
        $license_obj=$data->license;

        if (empty($license_obj)) {
            $repo_lincense="-";
        }
        else {
            $repo_lincense=strtoupper($license_obj->key);
        }
    }
   

    // 开始输出缓冲区
    ob_start(); 
    ?>

<div class="github-repo-card">
    <div class="github-repo-header">
        <img src="https://avatars.githubusercontent.com/u/9919?s=200&v=4" alt="GitHub logo" class="github-repo-logo">
        <div class="github-repo-title">
            <a href="<?php echo esc_url($data->html_url??""); ?>" target="_blank"><?php echo esc_html($data->full_name??""); ?></a>
        </div>
        <div class="github-repo-stats">
            Star: <?php echo intval($data->stargazers_count??""); ?> | Fork: <?php echo intval($data->forks_count??""); ?>
        </div>
    </div>
    <div class="github-repo-main">
        <div class="github-repo-desc">
            <p><?php echo esc_html($data->description??""); ?></p>
        </div>
        <div class="github-repo-commits">
            <h6>Recently Commits:</h6>
            <?php 
        // 使用缓存来获取提交记录，缓存时间为 1 小时
        $commits_cache_key = "github_repo_commits_{$username}_{$repo}";
        $cached_commits = get_transient($commits_cache_key);

        if ($cached_commits !== false) {
            // 如果有缓存的提交记录
            $commits_data = $cached_commits;
        } else {
            // 如果没有缓存，则从 GitHub API 获取提交记录
            $commits_api_url = "https://api.github.com/repos/$username/$repo/commits";
            $commits_response = wp_remote_get($commits_api_url, array(
                'headers' => array(
                    'User-Agent' => 'WordPress'
                )
            ));

            if (!is_wp_error($commits_response)) {
                $commits_body = wp_remote_retrieve_body($commits_response);
                $commits_data = json_decode($commits_body);

                // 设置提交记录缓存
                set_transient($commits_cache_key, $commits_data, HOUR_IN_SECONDS);
            }
        }

        $last_date="";
        // 展示提交记录
        if (!empty($commits_data) && is_array($commits_data)) {

            $commits=array_slice($commits_data,0,3);
            $last_commit=$commits[0];
            $last_date=date('Y-m-d', strtotime($last_commit->commit->author->date));
            echo "<ul>";
            foreach ($commits as $commit) {
                $commit_html_url=$commit->html_url;
                $sha=substr($commit->sha, 0, 7);
                echo "<li><a href='{$commit_html_url}' target='_blank'>
                {$sha}</a> 
                {$commit->commit->message} by {$commit->commit->author->name}<span>".date('Y-m-d', strtotime($commit->commit->author->date)) ."</span></li>";
            }
            echo "</ul>";
        }
        ?>
        </div>
    </div>
    
    <div class="github-repo-footer">
        <span>License：<?php echo $repo_lincense??""; ?></span>
        <a href="https://github.com/<?php echo $username; ?>/<?php echo $repo; ?>/releases" target="_blank" class="download-btn">Download</a>
    </div>
    
</div>

    <?php
    return ob_get_clean(); // 返回缓冲内容
}
