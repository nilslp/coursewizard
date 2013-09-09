tinyMCEPopup.requireLangPack();

var fancybuttonsDialog = {

	fancybutton : false,

	init : function() {		

		var f = document.forms[0];
		this.setUpDefaults();

	},

	setUpDefaults : function() {

		var eddom = tinyMCE.activeEditor.dom;
		var mydom = tinyMCEPopup.editor.dom;
		var f = document.forms[0];

		var parentNode = eddom.getParent(tinyMCE.activeEditor.selection.getNode(),'a.fancy-button');
		var thisNode = eddom.get(tinyMCE.activeEditor.selection.getNode(),'a.fancy-button');
		if (!parentNode) {
			parentNode = eddom.select('a.fancy-button', tinyMCE.activeEditor.selection.getNode());
			parentNode.length && (parentNode = parentNode[0]);
		}

		if (parentNode != 0) {
		
			// get each property of the button:
			var properties = {};

			// URL Check
			properties.urlAtt = parentNode.getAttribute('href');
			if (properties.urlAtt <= 0 || properties.urlAtt == null) {
				properties.url = "";
			} else {
				properties.url = properties.urlAtt.replace(/(.*\')(.*)('\.*)/g, '$2');
			};

			// Image URL Check
			properties.imageUrlAtt = eddom.select('.image img', parentNode)[0];
			if (properties.imageUrlAtt) {
				properties.imageUrlAtt = properties.imageUrlAtt.getAttribute('src');
			} else {
				properties.imageUrlAtt = 0;
			}

			if (properties.imageUrlAtt <= 0 || properties.imageUrlAtt == null) {
				properties.imageUrl = "";
			} else {
				properties.imageUrl = properties.imageUrlAtt.replace(/(.*\(')(.*)('\).*)/g, '$2');
			}

			// Header Text Check
			properties.headerText = eddom.select('.header-text', parentNode)[0];
			if (properties.headerText) {
				properties.headerText = properties.headerText.innerHTML;
			} else {
				properties.headerText = "";
			}

			// Pop Up Check
			properties.popupText = eddom.select('.popup-text', parentNode)[0];
			if (properties.popupText) {
				properties.popupText = properties.popupText.innerHTML;
			} else {
				properties.popupText = "";
			}

			// Pop Up Background Colors
			properties.popupBgc = eddom.select('.popup-content', parentNode)[0];
			if (properties.popupBgc.style.backgroundColor == "") {
				properties.popupBgc = "";
			} else {
				properties.popupBgc = document.defaultView.getComputedStyle(properties.popupBgc, "").backgroundColor;
			}

			// Pop Up Text Colors
			properties.popupColor = eddom.select('.popup-content', parentNode)[0];
			if (properties.popupColor.style.color == "") {
				properties.popupColor = "";
			} else {
				properties.popupColor = document.defaultView.getComputedStyle(properties.popupColor, "").color;
			}

			// Size Check
			properties.btnSize = parentNode.getAttribute('class');
			// If btnSize contains the word big
			if (properties.btnSize.indexOf('big') >= 0) {
				properties.btnSize = "big";
			} else {
				properties.btnSize = "small";
			}

			// Border Check
			properties.btnBorder = parentNode.getAttribute('class');
			// If btnBorder contains the word rounded
			if (properties.btnBorder.indexOf('rounded') >= 0) {
				properties.btnBorder = "rounded";
			} else {
				properties.btnBorder = "sharp";
			}

			// PopUp Displayed Check
			properties.popupVisible = parentNode.getAttribute('class');
			// If popupVisible contains the word rounded
			if (properties.popupVisible.indexOf('popup-disabled') >= 0) {
				properties.popupVisible = "popup-disabled";
			} else {
				properties.popupVisible = "popup-enabled";
			}
			
			this.populateForm(document.forms[0], properties);

		} else {

			var inputDelete = document.getElementById('fancy-button-delete');
			inputDelete.remove();
			return;

		}
		
	},

	populateForm : function(form, props) {

		form['fancy_btn_url'].value = props.url;
		form['fancy_btn_image'].value = props.imageUrl;
		form['fancy_btn_header'].value = props.headerText;
		form['fancy_btn_popuptext'].value = props.popupText;
		form['fancy_btn_size'].value = props.btnSize;
		form['fancy_btn_popupbgc'].value = props.popupBgc;
		form['fancy_btn_popupc'].value = props.popupColor;
		form['fancy_btn_popupshow'].value = props.popupVisible;
		form['fancy_btn_border'].value = props.btnBorder;

	},

	createFancyButton : function() {

		var eddom = tinyMCE.activeEditor.dom;	
		var mydom = tinyMCEPopup.editor.dom;
		var f = document.forms[0];
		var parentNode = eddom.getParent(tinyMCE.activeEditor.selection.getNode(),'a.fancy-button');

		// Remove InnerHTML of Selected Node when Updating a Current Fancy Button
		if (!parentNode) {
		} else {
			parentNode.remove();
		}

		// Basic Settings - GET
		var	ba_url = mydom.select('input#fancy_btn_url',f); // URL
		var ba_image = mydom.select('input#fancy_btn_image',f); // Image URL
		var ba_header = mydom.select('input#fancy_btn_header',f); // Header Text
		var ba_popuptext = mydom.select('textarea#fancy_btn_popuptext',f); // Pop Up Text

		// Advanced Settings - GET
		var ad_bgc = mydom.select('input#fancy_btn_bgc',f); // Primary BGC
		var ad_popupc = mydom.select('input#fancy_btn_popupc',f); // Pop Up Text C
		var ad_popupbgc = mydom.select('input#fancy_btn_popupbgc',f); // Pop Up BGC
		var ad_popupshow = mydom.select('select#fancy_btn_popupshow',f); // Hide/Show Pop Up
		var ad_border = mydom.select('select#fancy_btn_border',f); // Border Style

		// Easier to Read version
		var content = '\
			<span>\
				<a class="fancy-button ' + ad_border[0].value + ' ' + ad_popupshow[0].value + '" href="' + ba_url[0].value + '" title="">\
					<span class="popup-content" style="background: ' + ad_popupbgc[0].value + '; color: ' + ad_popupc[0].value + ';">\
						<span class="popup-text paragraph-text">' + ba_popuptext[0].value + '</span>\
					</span>\
					<span class="inner" style="background: ' + ad_bgc[0].value + '; ">\
						<span class="image">\
							<img src="' + ba_image[0].value + '" alt="' + ba_header[0].value + '" />\
							<span class="button-content">\
								<span class="header-text">' + ba_header[0].value + '</span>\
							</span>\
						</span>\
					</span>\
				</a>\
			</span>';
		return content;

	},

	insert : function() {

		var eddom = tinyMCE.activeEditor.dom;
		var new_fancybutton = this.createFancyButton();

		tinyMCE.activeEditor.execCommand('mceInsertContent', false, new_fancybutton);
		tinyMCEPopup.close();

	}

};

tinyMCEPopup.onInit.add(fancybuttonsDialog.init, fancybuttonsDialog);

function fancyButtonDelete() {

	var eddom = tinyMCE.activeEditor.dom;
	var parentNode = eddom.getParent(tinyMCE.activeEditor.selection.getNode(),'a.fancy-button');

	parentNode.remove();
	tinyMCEPopup.close();

}