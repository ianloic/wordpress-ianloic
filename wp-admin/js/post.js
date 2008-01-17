// this file shoudl contain all the scripts used in the post/edit page

function new_tag_remove_tag() {
	var id = jQuery( this ).attr( 'id' );
	var num = id.substr( 10 );
	var current_tags = jQuery( '#tags-input' ).val().split(',');
	delete current_tags[num];
	var new_tags = [];
	jQuery.each( current_tags, function( key, val ) {
		if ( val && !val.match(/^\s+$/) && '' != val ) {
			new_tags = new_tags.concat( val );
		}
	});
	jQuery( '#tags-input' ).val( new_tags.join( ',' ).replace( /\s*,+\s*/, ',' ).replace( /,+/, ',' ).replace( /,+\s+,+/, ',' ).replace( /,+\s*$/, '' ).replace( /^\s*,+/, '' ) );
	tag_update_quickclicks();
	return false;
}

function tag_update_quickclicks() {
	var current_tags = jQuery( '#tags-input' ).val().split(',');
	jQuery( '#tagchecklist' ).empty();
	shown = false;
//	jQuery.merge( current_tags, current_tags ); // this doesn't work anymore, need something to array_unique
	jQuery.each( current_tags, function( key, val ) {
		val = val.replace( /^\s+/, '' ).replace( /\s+$/, '' ); // trim
		if ( !val.match(/^\s+$/) && '' != val ) { 
			txt = '<span><a id="tag-check-' + key + '" class="ntdelbutton">X</a>&nbsp;' + val + '</span> ';
			jQuery( '#tagchecklist' ).append( txt );
			jQuery( '#tag-check-' + key ).click( new_tag_remove_tag );
			shown = true;
		}
	});
	if ( shown )
		jQuery( '#tagchecklist' ).prepend( '<strong>'+postL10n.tagsUsed+'</strong><br />' );
}

function tag_flush_to_text() {
	var newtags = jQuery('#tags-input').val() + ',' + jQuery('#newtag').val();
	// massage
	newtags = newtags.replace( /\s+,+\s*/g, ',' ).replace( /,+/g, ',' ).replace( /,+\s+,+/g, ',' ).replace( /,+\s*$/g, '' ).replace( /^\s*,+/g, '' );
	jQuery('#tags-input').val( newtags );
	tag_update_quickclicks();
	jQuery('#newtag').val('');
	jQuery('#newtag').blur();
	return false;
}

function tag_press_key( e ) {
	if ( 13 == e.keyCode ) {
		tag_flush_to_text();
		return false;
	}
}

function add_postbox_toggles() {
	jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
	jQuery('.togbox').click( function() { jQuery(jQuery(this).parent().parent().get(0)).toggleClass('closed'); save_postboxes_state(); } );
}

function save_postboxes_state() {
	var closed = jQuery('.postbox').filter('.closed').map(function() { return this.id; }).get().join(',');
	jQuery.post(postL10n.requestFile, {
		action: 'closed-postboxes',
		closed: closed,
		cookie: document.cookie});
}

function edit_permalink(post_id) {
	var e = jQuery('#editable-post-name');
	var revert_e = e.html();	
	var real_slug = jQuery('#post_name');
	var b = jQuery('#edit-slug-buttons');
	var revert_b = b.html();
	var old_slug = e.children('span').html();

	b.html('<a href="" class="save">'+postL10n.save+'</a> <a class="cancel" href="">'+postL10n.cancel+'</a>');
	b.children('.save').click(function() {
		var new_slug = e.children('input').attr('value');
		jQuery.post(postL10n.requestFile, {
			action: 'sample-permalink',
			post_id: post_id,
			new_slug: new_slug,
			cookie: document.cookie}, function(data) {
				jQuery('#sample-permalink').html(data);
				b.html(revert_b);
				real_slug.attr('value', new_slug);	
			});
		return false;
	});
	jQuery('#edit-slug-buttons .cancel').click(function() {
		e.html(revert_e);
		b.html(revert_b);
		real_slug.attr('value', revert_e);
		return false;
	});
	e.html('<input type="text" id="new-post-slug" value="" />').children('input').keypress(function(e){
		var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
		// on enter, just save the new slug, don't save the post
		if (13 == key) {b.children('.save').click();return false;}
		if (27 == key) {b.children('.cancel').click();return false;}
		real_slug.attr('value', this.value)}).focus();
}

addLoadEvent( function() {
	jQuery('#tags-input').hide();
	tag_update_quickclicks();
	// add the quickadd form
	jQuery('#jaxtag').prepend('<span id="ajaxtag"><input type="text" name="newtag" id="newtag" class="form-input-tip" size="16" autocomplete="off" value="'+postL10n.addTag+'" /><input type="button" class="button" id="tagadd" value="' + postL10n.add + '"/><input type="hidden"/><input type="hidden"/><span class="howto">'+postL10n.separate+'</span></span>');
	jQuery('#tagadd').click( tag_flush_to_text );
	jQuery('#newtag').focus(function() {
		if ( this.value == postL10n.addTag )
			jQuery(this).val( '' ).removeClass( 'form-input-tip' );
	});
	jQuery('#newtag').blur(function() {
		if ( this.value == '' )
			jQuery(this).val( postL10n.addTag ).addClass( 'form-input-tip' );
	});

	// auto-suggest stuff
	jQuery('#newtag').suggest( 'admin-ajax.php?action=ajax-tag-search', { delay: 500, minchars: 2 } );
	jQuery('#newtag').keypress( tag_press_key );

	// postboxes
	add_postbox_toggles();

	// category tabs
	var categoryTabs =jQuery('#category-tabs').tabs();

	// Ajax Cat
	var newCat = jQuery('#newcat').one( 'focus', function() { jQuery(this).val( '' ).removeClass( 'form-input-tip' ) } );
	jQuery('#category-add-sumbit').click( function() { newCat.focus(); } );
	var newCatParent = false;
	var newCatParentOption = false;
	var noSyncChecks = false; // prophylactic. necessary?
	var syncChecks = function() {
		if ( noSyncChecks )
			return;
		noSyncChecks = true;
		var th = jQuery(this);
		var c = th.is(':checked');
		var id = th.val().toString();
		jQuery('#in-category-' + id + ', #in-popular-category-' + id).attr( 'checked', c );
		noSyncChecks = false;
	};
	var catAddAfter = function( r, s ) {
		if ( !newCatParent ) newCatParent = jQuery('#newcat_parent');
		if ( !newCatParentOption ) newCatParentOption = newCatParent.find( 'option[value=-1]' );
		jQuery(s.what + ' response_data', r).each( function() {
			var t = jQuery(jQuery(this).text());
			t.find( 'label' ).each( function() {
				var th = jQuery(this);
				var val = th.find('input').val();
				var id = th.find('input')[0].id
				jQuery('#' + id).change( syncChecks );
				if ( newCatParent.find( 'option[value=' + val + ']' ).size() )
					return;
				var name = jQuery.trim( th.text() );
				var o = jQuery( '<option value="' +  parseInt( val, 10 ) + '"></option>' ).text( name );
				newCatParent.prepend( o );
			} );
			newCatParentOption.attr( 'selected', true );
		} );
	};
	jQuery('#categorychecklist').wpList( {
		alt: '',
		response: 'category-ajax-response',
		addAfter: catAddAfter
	} );
	jQuery('#category-add-toggle').click( function() {
		jQuery(this).parents('div:first').toggleClass( 'wp-hidden-children' );
		categoryTabs.tabsClick( 1 );
		return false;
	} );
	jQuery('.categorychecklist :checkbox').change( syncChecks ).filter( ':checked' ).change();

	jQuery('#editable-post-name').click(function() {jQuery('#edit-slug-buttons').children('.edit-slug').click()});
});
