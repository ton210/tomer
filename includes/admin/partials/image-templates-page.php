<?php
/**
 * Image Templates Page Template
 */
if(!defined('WPINC'))die;
?>

<div class="wrap">
    <h1><?php esc_html_e('Image Templates', 'sspu'); ?></h1>
    
    <div class="template-management">
        <div class="template-categories">
            <button class="button category-filter active" data-category="all">
                <?php esc_html_e('All', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="background">
                <?php esc_html_e('Background', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="lifestyle">
                <?php esc_html_e('Lifestyle', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="variations">
                <?php esc_html_e('Variations', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="branding">
                <?php esc_html_e('Branding', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="enhancement">
                <?php esc_html_e('Enhancement', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="hero">
                <?php esc_html_e('Hero Shots', 'sspu'); ?>
            </button>
            <button class="button category-filter" data-category="custom">
                <?php esc_html_e('Custom', 'sspu'); ?>
            </button>
        </div>
        
        <div class="template-actions">
            <button id="create-new-template" class="button button-primary">
                <?php esc_html_e('Create New Template', 'sspu'); ?>
            </button>
        </div>
        
        <div id="templates-list"></div>
    </div>
</div>