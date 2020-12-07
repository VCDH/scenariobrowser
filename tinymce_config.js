tinymce.init({ 
	selector: 'textarea',
	plugins: 'link lists charmap paste table textcolor',
	menu: {
		edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
		insert: {title: 'Insert', items: 'link charmap inserttable'},
		format: {title: 'Format', items: 'formats | bold italic underline strikethrough superscript subscript | removeformat'},
		table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
	},
	toolbar: 'bold italic underline striketrough | alignleft aligncenter alignright | bullist numlist | indent outdent | link unlink | forecolor backcolor | removeformat',
	resize: true,
	browser_spellcheck: true,
	language: 'nl',
	language_url: 'tinymce/langs/nl.js',
	element_format: 'html'
});
