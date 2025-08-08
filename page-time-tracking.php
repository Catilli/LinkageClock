<?php
/*
 * Template Name: Time Tracking
 */

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Time Tracking</h1>
        
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <form method="post" class="space-y-6">
                <div class="form-group">
                    <label for="work_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Work Date:
                    </label>
                    <input type="date" 
                           name="work_date" 
                           id="work_date" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" />
                </div>
                
                <div class="form-group">
                    <label for="hours_worked" class="block text-sm font-medium text-gray-700 mb-2">
                        Hours Worked:
                    </label>
                    <input type="number" 
                           step="0.01" 
                           name="hours_worked" 
                           id="hours_worked" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" />
                </div>
                
                <div class="form-group">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes:
                    </label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
                        Log Time
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>
