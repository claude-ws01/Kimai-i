/*! ki_budget */
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) 2006-2009 Kimai-Development-Team // http://www.kimai.org
 *
 * Kimai-i is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai-i is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

// ==========================
// Budget extension functions
// ==========================

function bud_ext_onload() {

    set_lists_visibility(true, $('#gui').find('div.ext.ki_budget').attr('id'));

    bud_ext_resize();
    $("#loader").hide();
    lists_visible(true);
    try {
        bud_ext_reload();
    } catch (e) {
        alert(e);
    }
}

/**
 * If the extension is being shrinked so the sublists are shown larger
 * adjust to that.
 */
function bud_ext_set_heightTop() {

	bud_ext_get_dimensions();
    if (!extensionShrinkMode) {
        $("#budgetArea").css("height", bud_height);
    } else {
        $("#budgetArea").css("height", "20px");
    }

}

/**
 * Update the dimension variables to reflect new height and width.
 */
function bud_ext_get_dimensions() {

    (customerShrinkMode) ? subtableCount = 2 : subtableCount = 3;
    subtableWidth = (pageWidth() - 10) / subtableCount - 7;

    bud_width = pageWidth() - 24;
    bud_height = pageHeight() - 198 - headerHeight() - 28;
}

function bud_ext_resize() {

    bud_ext_set_heightTop();
}


function bud_ext_plot(plotdata) {

    var target;
    for (var projectId in plotdata) {
        var background = false;
        for (var activityId in plotdata[projectId]) {
            if (activityId == 0) {
                target = 'budget_chartdiv_' + projectId;
            } else {
                target = 'budget_chartdiv_' + projectId + '_activity_' + activityId;
            }
            if ($('#' + target).length == 0) {
                continue;
            }
            var actualData = [];
            // turn the object into an array, since the jqplot wants an array, eventhough it's considered bad practice
            for (var index in plotdata[projectId][activityId]) {
                if (index == 'exceeded') {
                    // here we could add some more highlighting, for example setting the background to a color
                    //        		background = 'red';
                    $('#' + target).parent().children('.budget').css('color', 'red');
                } else if (index == 'approved_exceeded') {
                    // here we could add some more highlighting, for example setting the background to a color
                    //        		if(background != 'red') {
                    //        		background = 'yellow';
                    $('#' + target).parent().children('.approved').css('color', 'red');
                    //        		}
                } else if (index == 'approved' || index == 'budget_total' || index == 'billable_total' || index == 'approved_total' || index == 'total') {
                    // do nothing, that just means we have not used up the approved budget or is a number
                    // we use to display
                    // but we don't want it in the chart anyways
                } else if (index == 'name') {
                    // ignore
                } else {
                    if (background == false) {
                        background = $('#' + target).css("background-color");
                    }
                    actualData.push(new Array(index, plotdata[projectId][activityId][index]));
                }
            }
            try {
                $.jqplot(target, [actualData], {
                    seriesDefaults: {renderer: $.jqplot.PieRenderer,
                        rendererOptions: {padding: 10,
                            showDataLabels: true,
                            // By default, data labels show the percentage of the donut/pie.
                            // You can show the data 'value' or data 'label' instead.
                            dataLabels: 'value'
                        }
                    },
                    // Show the legend and put it outside the grid, but inside the
                    // plot container, shrinking the grid to accomodate the legend.
                    // A value of "outside" would not shrink the grid and allow
                    // the legend to overflow the container.
                    legend: {
                        show: true,
                        placement: 'outsideGrid'
                    },
                    seriesColors: chartColors,
                    grid: {background: background, borderWidth: 0, shadow: false}
                });
            }
            catch (err) {
                // probably no data, so remove the chart
                $('#' + target).remove();
                //        	alert(err.toSource());

            }
        }
    }
}

// ----------------------------------------------------------------------------------------
// reloads timesheet, customer, project and activity tables
//
function bud_ext_reload() {

    $('#ajax_wait').show();

    $.ajax({
        dataType: "html",
        url: bud_ext_path + 'processor.php',
        type: 'POST',
        data: {
            axAction: "reload",
            axValue: filterUsers.join(":") + '|' + filterCustomers.join(":") + '|' + filterProjects.join(":"),
            first_day: new Date($('#pick_in').val()).getTime() / 1000, last_day: new Date($('#pick_out').val()).getTime() / 1000,
            id: 0
        },
        success: function (data) {
            $('#ajax_wait').hide();
            $('#budgetArea').html(data);
        },
        error: function (error) {
            $('#ajax_wait').hide();
            alert(error);
        }
    });
}
