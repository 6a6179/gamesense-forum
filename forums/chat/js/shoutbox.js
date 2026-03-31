/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Overrides functionality for the shoutbox view:

	ajaxChat.shoutboxRowColors = {
		rowOdd: '#313130',
		rowEven: '#282827'
	};

	ajaxChat.handleLogout = function() {
	}

	ajaxChat.getDeletionLink = function() {
		return '';
	}

	ajaxChat.getRoleClass = function(roleID) {
		switch(parseInt(roleID, 10)) {
			case 0:
				return 'guest';
			case 1:
				return 'user';
			case 2:
				return 'moderator';
			case 3:
				return 'admin';
			case 4:
				return 'chatBot';
			case 5:
				return 'premium';
			default:
				return 'default';
		}
	}

	ajaxChat.getLastChatListRow = function() {
		var chatList = this.dom && this.dom['chatList'] ? this.dom['chatList'] : null;
		var node;

		if(!chatList)
			return null;

		node = chatList.lastChild;
		while(node && node.nodeType !== 1)
			node = node.previousSibling;

		return node;
	}

	ajaxChat.updateShoutboxFillColor = function() {
		var chatList = this.dom && this.dom['chatList'] ? this.dom['chatList'] : null;
		var lastRow = this.getLastChatListRow();
		var fillClass = 'rowOdd';

		if(!chatList)
			return;

		if(lastRow && lastRow.className && lastRow.className.indexOf('rowOdd') !== -1)
			fillClass = 'rowEven';

		chatList.style.backgroundColor = this.shoutboxRowColors[fillClass];
	}

	ajaxChat.clearChatList = (function(originalClearChatList) {
		return function() {
			originalClearChatList.call(this);
			this.updateShoutboxFillColor();
		};
	})(ajaxChat.clearChatList);

	ajaxChat.addMessageToChatList = (function(originalAddMessageToChatList) {
		return function() {
			originalAddMessageToChatList.apply(this, arguments);
			this.updateShoutboxFillColor();
		};
	})(ajaxChat.addMessageToChatList);

	ajaxChat.updateChatListRowClasses = (function(originalUpdateChatListRowClasses) {
		return function(node) {
			originalUpdateChatListRowClasses.call(this, node);
			this.updateShoutboxFillColor();
		};
	})(ajaxChat.updateChatListRowClasses);

	ajaxChat.getChatListMessageString = function(dateObject, userID, userName, userRole, messageID, messageText, channelID, ip) {
		var rowClass = this.DOMbufferRowClass;
		var userClass = this.getRoleClass(userRole);
		var colon = ': ';

		if(messageText.indexOf('/action') === 0 || messageText.indexOf('/me') === 0 || messageText.indexOf('/privaction') === 0) {
			userClass += ' action';
			colon = ' ';
		}

		var dateTime = '<span class="dateTime">[' + this.formatDate('%H:%i:%s', dateObject) + ']</span> ';
		return '<div id="'
			+ this.getMessageDocumentID(messageID)
			+ '" class="'
			+ rowClass
			+ '">'
			+ this.getDeletionLink(messageID, userID, userRole, channelID)
			+ dateTime
			+ '<a href="profile.php?id='
			+ userID
			+ '"><span class="'
			+ userClass
			+ '"'
			+ this.getChatListUserNameTitle(userID, userName, userRole, ip)
			+ ' dir="'
			+ this.baseDirection
			+ '">'
			+ userName
			+ '</span></a>'
			+ colon
			+ this.replaceText(messageText)
			+ '</div>';
	}
