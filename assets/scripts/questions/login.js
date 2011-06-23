Ext.onReady(function() {
	var login_form = Ext.create('Ext.form.Panel', {
		url: site_url + 'questions/login',
		standardSubmit: true,
		frame: true,
		title: 'Log into CS50Help',
		width: 350,
		defaultType: 'textfield',
		defaults: {
			anchor: '100%'
		},
		
		items: [{
			fieldLabel: 'Name',
			name: 'name',
			allowBlank: false
		}],

		buttons: [{
			text: 'Submit',
			handler: function() {
				login_form.getForm().submit();
			}
		}]
	});

	login_form.render(document.body);
});
