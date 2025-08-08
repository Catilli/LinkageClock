<?php
/*
 * Template Name: Time Tracking
 */

get_header(); ?>

<div class="container">
    <div class="time-tracking-form">
        <h1>Time Tracking</h1>
        
        <form method="post">
            <div class="form-group">
                <label for="work_date">Work Date:</label>
                <input type="date" name="work_date" id="work_date" required />
            </div>
            
            <div class="form-group">
                <label for="hours_worked">Hours Worked:</label>
                <input type="number" step="0.01" name="hours_worked" id="hours_worked" required />
            </div>
            
            <div class="form-group">
                <label for="notes">Notes:</label>
                <textarea name="notes" id="notes"></textarea>
            </div>
            
            <button type="submit">Log Time</button>
        </form>
    </div>
</div>

<?php get_footer(); ?>
