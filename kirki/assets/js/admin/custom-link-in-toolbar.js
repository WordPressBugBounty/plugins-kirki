// custom-link-in-toolbar.js
// wrapped into IIFE - to leave global space clean.
(function (window, wp) {
	let timeID = null;
	const editWithBtnHTML = `<a class="kirki-edit-with-btn">
	<style>
			.kirki-edit-with-btn{
				display: inline-flex;
				align-items: center;
				height: 32px;
				border-radius: 8px;
				padding-right: 12px;
				padding-left: 12px;
				font-weight: 500;
				font-size: 11px;
				line-height: 16px;
				letter-spacing: -0.2px;
				color: #FFFFFF;
				background: #167BFF;
				gap: 4px;
				white-space: nowrap;
				text-decoration: none;
				cursor: pointer;
			}
			.kirki-edit-with-btn:hover{
				background: #1670E7;
				color: #fff;
			}
		 .kirki-edit-svg{
				width: 14px;
				height: 14px;
			}
		</style>
		<span>Design with Kirki</span>
			<svg
			class="kirki-edit-svg">
			xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14"><path fill="#fff" d="M9.679 1.297a2.13 2.13 0 1 1 3.013 3.01l-8.315 8.316-.087.077a1 1 0 0 1-.304.164L.754 13.94l-.081.021a.55.55 0 0 1-.616-.716l1.079-3.234c.049-.146.13-.28.24-.39l5.948-5.964-.005-.005.707-.707.004.004zM1.29 12.707l1.562-.52-1.042-1.042zm.955-2.542 1.587 1.588 5.793-5.794L8.03 4.365zm9.732-8.168a1.13 1.13 0 0 0-1.591.006l-1.65 1.654 1.596 1.595L11.985 3.6a1.13 1.13 0 0 0-.007-1.603"/></svg>
		</a>`;
	const backToWordpressEditBtnHTML = `
		<a class="kirki-back-dash-btn">
		<style>
		.kirki-back-dash-btn{
			background: #0000000A;
			height: 32px;
			border-radius: 8px;
			padding-right: 12px;
			padding-left: 12px;
			gap: 4px;
			font-weight: 500;
			font-size: 11px;
			line-height: 16px;
			letter-spacing: -0.2px;
			text-decoration: none;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
		}
		.kirki-back-dash-btn:hover{
			background: #00000014;
			color: #000;
		}
		.kirki-back-dash-svg{
			width: 14px;
			height: 14px;
		}
		</style>
		<svg
		class="kirki-back-dash-svg">
		xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14"><path fill="#000" fill-rule="evenodd" d="M8.354 10.354a.5.5 0 0 1-.707 0l-3-3a.5.5 0 0 1-.063-.631l.063-.076 3-3a.5.5 0 0 1 .707.707L5.707 7l2.647 2.647a.5.5 0 0 1 0 .707" clip-rule="evenodd"/></svg>
			<span>Back to WordPress Editor</span>
		</a>
	`;

	const backtoWPandEditKirkiBtn = `<div class="kirki-editor-button-wrapper" style="min-height: 300px; background: #f6f7f7; display: flex; justify-content: center; align-items: center;margin-top: 10px;"><div style="display: flex; gap:8px;">${backToWordpressEditBtnHTML}${editWithBtnHTML}</div></div>`;

	const createTextToHtmlElement = (text) => {
		const div = document.createElement('div');
		div.innerHTML = text;

		return div.firstChild;
	};

	const handleClickEditWithBtn = (e) => {
		e.preventDefault();
		let title = document.querySelector('[name="post_title"]');
		if (title) {
			title = title.value;
		} else {
			// for gutenberg editor
			title = document.getElementsByClassName('wp-block-post-title')?.[0]?.innerText || '';
		}

		title = (title || '').replace(/^\uFEFF/, '').trim();
		if (!title) {
			title = 'Untitled';
		}

		const formData = new FormData();
		formData.append('action', 'kirki_post_apis');
		formData.append('endpoint', 'back-to-kirki-editor');
		formData.append('postId', kirki_admin.postId);
		formData.append('title', title);

		fetch(kirki_admin.ajaxUrl, {
			method: 'POST', // or 'PUT'
			body: formData,
			headers: {
				'X-WP-Nonce': kirki_admin.nonce,
			},
		})
			.then((response) => response.json())
			.then((data) => {
				window.location.href = kirki_admin.postEditURL;
			})
			.catch((error) => {
				console.error('Error:', error);
			});
	};
	const handleClickBackToWordpressBtn = () => {
		const formData = new FormData();
		formData.append('action', 'kirki_post_apis');
		formData.append('endpoint', 'back-to-wordpress-editor');
		formData.append('postId', kirki_admin.postId);

		fetch(kirki_admin.ajaxUrl, {
			method: 'POST', // or 'PUT'
			body: formData,
			headers: {
				'X-WP-Nonce': kirki_admin.nonce, // Add nonce as a custom header
			},
		})
			.then((response) => response.json())
			.then((data) => {
				if (data) {
				}
			})
			.catch((error) => {
				console.error('Error:', error);
			});
	};

	const classicAddEditWithSingleButton = () => {
		const editButtonWrapper = document.querySelector('#wp-content-media-buttons');
		if (!editButtonWrapper) return;
		editButtonWrapper.append(createTextToHtmlElement(editWithBtnHTML));
		const kirkiBtns = document.querySelectorAll(`.kirki-edit-with-btn`);
		kirkiBtns.forEach((kirkiBtn) => {
			kirkiBtn.addEventListener('click', handleClickEditWithBtn);
		});
	};
	const gutenbergAddEditWithSingleButton = () => {
		const editButtonWrapper = document.querySelector('.edit-post-header-toolbar');
		if (!editButtonWrapper) return;
		editButtonWrapper.append(createTextToHtmlElement(editWithBtnHTML));
		const kirkiBtns = document.querySelectorAll(`.kirki-edit-with-btn`);
		kirkiBtns.forEach((kirkiBtn) => {
			kirkiBtn.addEventListener('click', handleClickEditWithBtn);
		});
		clearInterval(timeID);
	};
	const classicAddKirkiButtonsInsideContentWrapper = () => {
		const contentDiv = document.querySelector('#postdivrich');
		if (!contentDiv) return;
		contentDiv.style.display = 'none';
		contentDiv.insertAdjacentHTML('afterend', backtoWPandEditKirkiBtn);

		const backToWPbtns = document.querySelectorAll(`.kirki-back-dash-btn`);
		backToWPbtns.forEach((backToWPbtn) => {
			backToWPbtn.addEventListener('click', () => {
				handleClickBackToWordpressBtn();
				removeButtonsFromContent(contentDiv);
			});
		});

		const kirkiBtns = document.querySelectorAll(`.kirki-edit-with-btn`);
		kirkiBtns.forEach((kirkiBtn) => {
			kirkiBtn.addEventListener('click', handleClickEditWithBtn);
		});
	};
	const gutenbergAddKirkiButtonsInsideContentWrapper = () => {
		let document = window.document;
		let contentDiv = document.querySelector('.is-root-container');
		if (!contentDiv) {
			const editorCanvasIframe = document.querySelector('[name="editor-canvas"]');
			if (!editorCanvasIframe) return;
			// Access the iframe's content window
			let iframeWindow = editorCanvasIframe.contentWindow;
			// Access the document inside the iframe
			let iframeDocument = iframeWindow.document;
			// Find the element inside the iframe using its class name
			contentDiv = iframeDocument.querySelector('.is-root-container');
			if (!contentDiv) {
				return;
			}
			document = iframeDocument;
		}
		contentDiv.style.display = 'none';
		contentDiv.insertAdjacentHTML('afterend', backtoWPandEditKirkiBtn);

		const backToWPbtns = document.querySelectorAll(`.kirki-back-dash-btn`);
		backToWPbtns.forEach((backToWPbtn) => {
			backToWPbtn.addEventListener('click', () => {
				handleClickBackToWordpressBtn();
				removeButtonsFromContent(contentDiv, document);
			});
		});

		const kirkiBtns = document.querySelectorAll(`.kirki-edit-with-btn`);
		kirkiBtns.forEach((kirkiBtn) => {
			kirkiBtn.addEventListener('click', handleClickEditWithBtn);
		});

		clearInterval(timeID);
	};
	const removeButtonsFromContent = (contentDiv, document = window.document) => {
		const buttons = document.querySelector('.kirki-editor-button-wrapper');
		buttons?.remove();
		contentDiv.style.display = 'block';
		classicAddEditWithSingleButton();
		gutenbergAddEditWithSingleButton();
	};

	const enableForClassicEditor = () => {
		if (kirki_admin.isEditorModeIsKirki == 1) {
			classicAddKirkiButtonsInsideContentWrapper();
		} else {
			classicAddEditWithSingleButton();
		}
	};

	const enableForGutenbergEditor = () => {
		timeID = setInterval(() => {
			if (kirki_admin.isEditorModeIsKirki == 1) {
				gutenbergAddKirkiButtonsInsideContentWrapper();
			} else {
				gutenbergAddEditWithSingleButton();
			}
		}, 1000);
	};

	enableForClassicEditor();
	enableForGutenbergEditor();
})(window, wp);
