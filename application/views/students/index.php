<script>

var student_id;
var hand_up = false;

function handle_question_submit() {
	if (hand_up)
		put_hand_down();
	else
		put_hand_up();
}

function put_hand_up() {
	var url = "<?= site_url('students/add'); ?>";
	var data = {
		name: $("#question-name").val(),
		question: $("#question-text").val(),
		category: $("#question-category").val()
	};

	$.post(url, data, function(response) {
		response = JSON.parse(response);
		if (response.success) {
			// disable form
			$("#question-submit").attr("value", "Put your hand down");
			$("#question-name, #question-text").val("").attr("disabled", "disabled");

			student_id = response.id;
			hand_up = true;
		}
		else {
			show_error();
		}
	});
}

function put_hand_down() {
	var url = "<?= site_url('students/hand_down'); ?>";
	var data = {
		id: student_id
	};

	$.post(url, data, function(response) {
		response = JSON.parse(response);
		if (response.success) {
			// enable form
			$("#question-submit").attr("value", "Ask");
			$("#question-name, #question-text").removeAttr("disabled");


			student_id = null;
			hand_up = false;
		}
		else {
			show_error();
		}
	});
}

function show_error() {
	alert("An error occurred. Try again");
}

$(document).ready(function() {
	$("#question-form").submit(function(e) {
		handle_question_submit();
		e.preventDefault();
		return false;
	});

	var url = "<?= site_url('spreadsheets/categories'); ?>";
	$.getJSON(url, function(response) {
		for (option in response) {
			var $option = $('<option>').attr('value', response[option]).text(response[option]);
			$("#question-category").append($option);
		}
	});
});


</script>

<h1>CS50 Help</h1>
<form action="#" id="question-form">
	<select name="category" id="question-category"></select>
	<input type="text" name="name" id="question-name" placeholder="Name" />
	<input type="text" name="question" id="question-text" placeholder="Question" />
	<input type="submit" value="Ask" id="question-submit" />
</form>
