/**
 * Wordpress plugin.
 */

(function() {
	var DOM = tinymce.DOM;

	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('wordpress');

	tinymce.create('tinymce.plugins.WordPress', {
		init : function(ed, url) {
			var t = this, tbId = ed.getParam('wordpress_adv_toolbar', 'toolbar2');
            var moreHTML = '<img src="' + url + '/img/trans.gif" class="mceWPmore mceItemNoResize" title="'+ed.getLang('wordpress.wp_more_alt')+'" />';
            var nextpageHTML = '<img src="' + url + '/img/trans.gif" class="mceWPnextpage mceItemNoResize" title="'+ed.getLang('wordpress.wp_page_alt')+'" />';

			// Hides the specified toolbar and resizes the iframe
			ed.onPostRender.add(function() {
				if ( ed.getParam('wordpress_adv_hidden', 1) ) {
					DOM.hide(ed.controlManager.get(tbId).id);
					t._resizeIframe(ed, tbId, 28);
				}
			});

			// Register buttons
			ed.addButton('wp_more', {
				title : 'wordpress.wp_more_desc',
				image : url + '/img/more.gif',
				onclick : function() {
					ed.execCommand('mceInsertContent', 0, moreHTML);
				}
			});

			ed.addButton('wp_page', {
				title : 'wordpress.wp_page_desc',
				image : url + '/img/page.gif',
				onclick : function() {
					ed.execCommand('mceInsertContent', 0, nextpageHTML);
				}
			});

			ed.addButton('wp_help', {
				title : 'wordpress.wp_help_desc',
				image : url + '/img/help.gif',
				onclick : function() {
					ed.windowManager.open({
						url : tinymce.baseURL + '/wp-mce-help.php',
						width : 450,
						height : 420,
						inline : 1
					});
				}
			});

			ed.addButton('wp_adv', {
				title : 'wordpress.wp_adv_desc',
				image : url + '/img/toolbars.gif',
				onclick : function() {
					var id = ed.controlManager.get(tbId).id, cm = ed.controlManager;

					if (DOM.isHidden(id)) {
						cm.setActive('wp_adv', 1);
						DOM.show(id);
						t._resizeIframe(ed, tbId, -28);
						ed.settings.wordpress_adv_hidden = 0;
					} else {
						cm.setActive('wp_adv', 0);
						DOM.hide(id);
						t._resizeIframe(ed, tbId, 28);
						ed.settings.wordpress_adv_hidden = 1;
					}
				}
			});

			// Add class "alignleft" or "alignright" when selecting align for images.
			ed.onBeforeExecCommand.add(function( editor, cmd ) {
				var node, dir, xdir;
	
				if ( ( cmd.indexOf('Justify') != -1 ) && ( node = editor.selection.getNode() ) ) {
					if ( node.nodeName !== 'IMG' ) return;
					dir = cmd.substring(7).toLowerCase();
					if ( 'JustifyCenter' == cmd || editor.queryCommandState( cmd ) ) {
						editor.dom.removeClass( node, "alignleft" );
						editor.dom.removeClass( node, "alignright" );
					} else {
						xdir = ( dir == 'left' ) ? 'right' : 'left';
						editor.dom.removeClass( node, "align"+xdir );
						editor.dom.addClass( node, "align"+dir );
					}
				}
			});
			
			// Add listeners to handle more break
			t._handleMoreBreak(ed, url);
		},

		getInfo : function() {
			return {
				longname : 'WordPress Plugin',
				author : 'WordPress', // add Moxiecode?
				authorurl : 'http://wordpress.org',
				infourl : 'http://wordpress.org',
				version : '1.0a1'
			};
		},

		// Internal functions

		// Resizes the iframe by a relative height value
		_resizeIframe : function(ed, tb_id, dy) {
			var ifr = ed.getContentAreaContainer().firstChild;

			DOM.setStyle(ifr, 'height', ifr.clientHeight + dy); // Resize iframe
			ed.theme.deltaHeight += dy; // For resize cookie
		},

		_handleMoreBreak : function(ed, url) {
			var moreHTML = '<img src="' + url + '/img/trans.gif" alt="$1" class="mceWPmore mceItemNoResize" title="'+ed.getLang('wordpress.wp_more_alt')+'" />';
            var nextpageHTML = '<img src="' + url + '/img/trans.gif" class="mceWPnextpage mceItemNoResize" title="'+ed.getLang('wordpress.wp_page_alt')+'" />';

			// Load plugin specific CSS into editor
			ed.onInit.add(function() {
				ed.dom.loadCSS(url + '/css/content.css');
			});

			// Display morebreak instead if img in element path
			ed.onPostRender.add(function() {
				if (ed.theme.onResolveName) {
					ed.theme.onResolveName.add(function(th, o) {
						if (o.node.nodeName == 'IMG') {
                            if ( ed.dom.hasClass(o.node, 'mceWPmore') )
                                o.name = 'wpmore';
                            if ( ed.dom.hasClass(o.node, 'mceWPnextpage') )
                                o.name = 'wppage';
                        }
							
					});
				}
			});

			// Replace morebreak with images
			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = o.content.replace(/<!--more(.*?)-->/g, moreHTML);
				o.content = o.content.replace(/<!--nextpage-->/g, nextpageHTML);
			});

			// Replace images with morebreak
			ed.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = o.content.replace(/<img[^>]+>/g, function(im) {
						if (im.indexOf('class="mceWPmore') !== -1) {
                            var m;
                            var moretext = (m = im.match(/alt="(.*?)"/)) ? m[1] : '';

                            im = '<!--more'+moretext+'-->';
                        }
                        if (im.indexOf('class="mceWPnextpage') !== -1)
							im = '<!--nextpage-->';
						
                        return im;
					});
			});

			// Set active buttons if user selected pagebreak or more break
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('wp_page', n.nodeName === 'IMG' && ed.dom.hasClass(n, 'mceWPnextpage'));
				cm.setActive('wp_more', n.nodeName === 'IMG' && ed.dom.hasClass(n, 'mceWPmore'));
			});
		}
	});

	// Register plugin
	tinymce.PluginManager.add('wordpress', tinymce.plugins.WordPress);
})();