var current_position = 0;
var hand_up = false;
var queue = [];
var question_id = null;

Ext.onReady(function() {
	Ext.create('Ext.data.Store', {
		storeId: 'queue_store',
		fields: ['id', 'student_id', 'name', 'question', 'position'],
		proxy: {
			type: 'memory',
			reader:  {
				type: 'json',
				root: course + '_queue',
			},
		},
	});

	Ext.create('Ext.data.Store', {
		storeId: 'category_store',
		fields: ['category'],
		proxy: {
			type: 'memory',
			reader:  {
				type: 'json',
				root: 'categories',
			},
		},
	});

	var viewport = Ext.create('Ext.container.Viewport',  {
		id: 'viewport',
		title: 'CS50 Help',
		renderTo: document.body,
		layout: {
			type: 'vbox',
			align: 'stretch',
		},
		items: [{
			xtype: 'form',
			height: 200,
			id: 'question-form',
			url: site_url + 'questions/dispatch',
			layout: { 
				type: 'vbox',
				align: 'stretch',
			},

			frame: true,
			title: 'Got a question?',
			defaults: {
				anchor: '100%'
			},
		
			items: [{
				xtype: 'textarea',
				name: 'question-text',
				id: 'question-text',
				allowBlank: false,
				emptyText: "Question text",
            }, {
                xtype: 'combobox',
                fieldLabel: 'Category',
				store: Ext.data.StoreManager.lookup('category_store'),
                queryMode: 'local',
                valueField: 'category',
                displayField: 'category',
			}, {
				xtype: 'checkbox',
				boxLabel: 'Show your question in the queue',
				name: 'question-show',
				id: 'question-show',
				checked: true,
			}],

			buttonAlign: 'center',
			buttons: [{
				text: 'Ask',
				id: 'question-submit',
				handler: function() {
					handle_question_submit();
				}
			}]
		}, { 
			xtype: 'panel',
			flex: 1,
			layout: {
				type: 'hbox',
				align: 'stretch',
			},
			items: [{
				xtype: 'gridpanel',
				flex: 1,
				id: 'queueContainer',
				title: 'Queue',
				store: Ext.data.StoreManager.lookup('queue_store'),
				columns: [
					{ header: 'Position', dataIndex: 'position', flex: 1 },
					{ header: 'Name', dataIndex: 'name', flex: 2 },
					{ header: 'Question', dataIndex: 'question', flex: 5 },
				],
			}, {
				xtype: 'tabpanel',
				flex: 1,
				id: 'tabs',
				title: 'Chat',
			}]
		}]
	});

	// ask / hand down button pressed
	$("#question-form").submit(function(e) {
		handle_question_submit();
		e.preventDefault();
		return false;
	});

	$(window).unload(function() {
		closed();
	});

	window.onunload = function() { closed(); };
	window.onbeforeunload = function() { closed(); };

	// timeout prevents eternally loading favicon 
	setTimeout(function() {
		get_categories();
		login();
		// get_queue will continue to call itself in a loop and call get_dispatch for the first time
		get_queue(true);
	}, 100);
});


/**
 * Add a new question to the queue
 *
 */
function add_question() {
	var url = site_url + 'api/v1/questions/add';

	var data = {
		student_id: identity,
		name: name,
		question: Ext.getCmp('question-text').getValue(),
		category: $('#question-category').val(),
		show: Number(Ext.getCmp('question-show').getValue()),
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
 * Student has closed the window
 *
 */
function closed() {
	// only post if student has an active question
	if (question_id) {
		var url = site_url + 'api/v1/questions/closed';
		var data = {
			id: question_id,
		};

		$.post(url, data, function(response) {
		});
	}
}

/**
 * Disallow students from asking a question (e.g. they already have an active question)
 *
 */
function disable_form() {
	Ext.getCmp('question-submit').setText('Put your hand down');
	Ext.getCmp('question-text').setDisabled(true);
	Ext.getCmp('question-text').setDisabled(true);
	$('#current-position').show();
	hand_up = true;
}

/**
 * Allow students to ask a question (e.g. they do not have an active question)
 *
 */
function enable_form() {
	Ext.getCmp('question-submit').setText('Ask');
	Ext.getCmp('question-text').setDisabled(false);
	$('#current-position').hide();
	hand_up = false;
}

/**
 * Get the question categories for today
 *
 */
function get_categories() {
	$.getJSON(site_url + 'api/v1/spreadsheets/categories', function(response) {
        // iterate over tab box
		var tabs = Ext.getCmp('tabs');
		var box = tabs.getBox();
		for (i in response.categories) {
            // add tab with unique XID for each category
			var tab = tabs.add({
				title: response.categories[i].category,
				id: 'tab-' + response.categories[i].category,
				layout: "fit",
				html: '<iframe src="http://facebook.com/plugins/livefeed.php?api_key=137798392964646&sdk=joey&always_post_to_friends=false&height=' + box.height + '&width=' + box.width + '&xid=\'' + response.categories[i] + '\'" style="height:' + box.height + 'px; width: ' + box.width + 'px">',
			});
		}
        
        // load categories into data store
        var store = Ext.data.StoreManager.lookup('category_store');
        store.loadData(response.categories);
	});
}

/**
 * Get all students' most recent dispatches today
 * @param initial True iff this is the first time getting dispatches
 *
 */
function get_dispatched(initial) {
	var url = site_url + 'api/v1/questions/dispatched';
	// force immediate response if called for the first time
	if (initial)
		url += '/true';

	$.getJSON(url, function(response) {
		if (response.success) {
			if (response.changed) {
				// see if our question has been dispatched
				var dispatch = response[course + '_dispatched'].filter(function(element, index, array) { return element.id == question_id });
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
	var url = site_url + 'api/v1/questions/queue';
	// force an immediate response if called for the first time
	if (initial)
		url += '/true';

	$.getJSON(url, function(response) {
		if (response.success) {
			// response indicates queue has changed, so rebuild
			if (response.changed) {
				// load queue into data store
				var store = Ext.data.StoreManager.lookup('queue_store');
				store.loadData(response[course + '_queue']);
				
				// get our question from the store if it exists
				question_index = store.findExact('student_id', identity);
				if (question_index > -1) {
					question_id = store.getAt(question_index).data.id;
					hand_up = true;
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
 * Mark closed questions as hand up
 *
 */
function login() {
	var url = site_url + 'api/v1/questions/login';
	var data = {
		student_id: identity,
	};

	$.post(url, data, function(response) {
	});
}

/**
 * Student has put their hand down for the current question
 *
 */
function put_hand_down() {
	var url = site_url + 'api/v1/questions/hand_down';
	var data = {
		id: question_id,
		student_id: identity,
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
