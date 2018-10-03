;
(function ($) {
	function addNewTask(title, flId) {
		console.log(title, flId);
		if (title !== 'undefined' && flId !== 'undefined') {
			var ajaxdata = {
				action     : 'add_new_task',
				freelancer_id : flId,
				task_title : title,
			};
			jQuery.post( ajax.url, ajaxdata, function( response ) {
				if(!alert( response )){window.location.reload();}
			});
		}
	}

	$(document).ready(function () {
		$('a[href*="#popup"]').each(function(){
			$(this).attr('data-toggle', 'modal').attr('data-target', $(this).attr('href'))
		})

		$('.create-new-task').on('click', function() {
			var title = $(this).prev('form').find('#taskTitle').val();
			var flId = $(this).prev('form').find('#freelancers option:selected').val();

			addNewTask(title, flId);
		});

		//init datatables
		$('#tasks_table').DataTable();
	});

}(jQuery));