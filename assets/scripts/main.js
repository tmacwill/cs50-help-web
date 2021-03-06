var BASE_URL = 'http://ohs.cs50.net';
var auth = null;
var current_position = 0;
var hand_up = false;
var queue = [];
var question_id = null;

/**
 * Add a new question to the queue
 *
 */
function add_question() {
	var url = BASE_URL + 'questions/add';

	var data = {
		name: auth,
		question: $('#question-text').val(),
		category: $('#question-category').val(),
		show: $('#question-show').is(':checked') ? 1 : 0
	};

	disable_form();

	$.post(url, data, function(response) {
		response = JSON.parse(response);
		if (response.success) {
			question_id = response.id;
			hand_up = true;
		}
		else
			show_error();
	});
}

/**
 * Disallow students from asking a question (e.g. they already have an active question)
 *
 */
function disable_form() {
	$('#question-submit').attr('value', 'Put your hand down');
	$('#question-name, #question-text').val('').attr('disabled', 'disabled');
	$('#current-position').show();
	hand_up = true;
}

/**
 * Allow students to ask a question (e.g. they do not have an active question)
 *
 */
function enable_form() {
	$('#question-submit').attr('value', 'Ask');
	$('#question-text').removeAttr('disabled');
	$('#current-position').hide();
	hand_up = false;
}

/**
 * Get the question categories for today
 *
 */
function get_categories() {
	var url = BASE_URL + 'spreadsheets/categories';
	$.getJSON(url, function(response) {
		for (option in response.categories) {
			var $option = $('<option>').attr('value', response.categories[option]).text(response.categories[option]);
			$("#question-category").append($option);
		}
	});
}

/**
 * Get all students' most recent dispatches today
 * @param initial True iff this is the first time getting dispatches
 *
 */
function get_dispatched(initial) {
	var url = BASE_URL + 'questions/dispatched';
	// force immediate response if called for the first time
	if (initial)
		url += '/true';

	$.getJSON(url, function(response) {
		if (response.success) {
			if (response.changed) {
				// see if our question has been dispatched
				var dispatch = response.dispatched.filter(function(element, index, array) { return element.id == question_id });
				// our question is considered dispatched until we ask another, so don't show multiple notifications for one dispatch
				if (dispatch.length > 0) {
					alert('WEE WOO WEE WOO YOUR TURN. GO SEE ' + dispatch[0].tf);

					question_id = null;
					hand_up = false;
					enable_form();
				}
			}

			// continue long-polling loop
			get_dispatched(false);
		}
		else
			show_error();
	});
}

/**
 * Get the current queue
 * @param initial True iff this is the first time getting the queue
 *
 */
function get_queue(initial) {
	var url = BASE_URL + 'questions/queue';
	// force an immediate response if called for the first time
	if (initial)
		url += '/true';

	$.getJSON(url, function(response) {
		if (response.success) {
			// response indicates queue has changed, so rebuild
			if (response.changed) {

				// clear existing data
				$('#queue').empty();
				queue = response.queue;

				for (var item in queue) {
					var li = $('<li>');
					li.text(queue[item].name + (parseInt(queue[item].show) ? ' - ' + queue[item].question : ''));

					// check if our user id matches the user id of the question
					// TODO: separate name from session token or whatever we're using
					if (auth == queue[item].name) {
						question_id = queue[item].id;
						current_position = item;

						$('#current-position-value').text(current_position);
					}

					$('#queue').append(li);
				}
			}

			if (initial) {
				// disable form if we have already asked a question
				if (question_id)
					disable_form();
				else
					enable_form();

				// begin dispatched long-polling loop
				setTimeout(function() { get_dispatched(true); }, 100);
			}

			// don't force multiple DB reads
			get_queue(false);
		}
		else
			show_error();
	});
}

/**
 * Event handler for hitting the ask / hand down button
 *
 */
function handle_question_submit() {
	if (hand_up)
		put_hand_down();
	else
		add_question();
}

/**
 * Student has put their hand down for the current question
 *
 */
function put_hand_down() {
	var url = BASE_URL + 'questions/hand_down';
	var data = {
		id: question_id
	};

	$.post(url, data, function(response) {
		response = JSON.parse(response);
		if (response.success) {
			enable_form();
			question_id = null;
			hand_up = false;
		}
		else
			show_error();
	});
}

/**
 * Show an error message
 * @param message Message text
 *
 */
function show_error(message) {
	alert(message || "An error occurred. Please alert the dispatcher.");
}

$(document).ready(function() {
	// CS50 ID supplies authentication cookie
	auth = $.cookie('cs50help_auth');

	// ask / hand down button pressed
	$("#question-form").submit(function(e) {
		handle_question_submit();
		e.preventDefault();
		return false;
	});

	// timeout prevents eternally loading favicon 
	setTimeout(function() {
		get_categories();
		// get_queue will continue to call itself in a loop and call get_dispatch for the first time
		get_queue(true);
	}, 100);
});
