var list; var extra;
jQuery(function($) {

var dimAfter = function( r, settings ) {
	var a = $('#awaitmod');
	var n = parseInt(a.html(),10) + ( $('#' + settings.element).is('.' + settings.dimClass) ? 1 : -1 );
	a.html( n.toString() );
}

var delAfter = function( r, settings ) {
	var a = $('#awaitmod');
	if ( a.parent('.current').size() || $('#' + settings.element).is('.unapproved') && parseInt(a.html(),10) > 0 ) {
		var n = parseInt(a.html(),10) - 1;
		a.html( n.toString() );
	}

	if ( extra.size() == 0 || extra.children().size() == 0 ) {
		return;
	}

	list[0].wpList.add( extra.children(':eq(0)').remove().clone() );
	$('#get-extra-comments').submit();
}

extra = $('#the-extra-comment-list').wpList( { alt: '', delColor: 'none', addColor: 'none' } );
list = $('#the-comment-list').wpList( { dimAfter : dimAfter, delAfter : delAfter, addColor: 'none' } );

} );
