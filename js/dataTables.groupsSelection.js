function RowSelector() {
	var me = {};

	me.element = undefined;
	me.nodes = undefined;
	me.selectedClass = undefined;
	me.unselectedClass = undefined;
	me.selecting = false;

	me.selectRows = function( table_id, trs ) {
		$( this.element, this.nodes ).addClass( this.selectedClass );
		var tt = TableTools.fnGetInstance( table_id );
		this.selecting = true;
		for(var i = 0; i < trs.length; i++) {
			tt.fnSelect( trs[i] );
		}
		this.selecting = false;
	}

	return me;
}

function GroupsCollection() {
	var o = {
		groups: {},
		getGroups: function( item ) {
			var groups = [];
			for( var group in this.groups ) {
				if ( this.groups.hasOwnProperty( group )) {
					if ( group.hasOwnProperty( item )) {
						groups.push( group );
					}
				}
			}
			return groups;
		},
		getItems: function( groupName ) {
			return this.groups[groupName];
		},
		addGroup: function( groupName, items ) {
			this.groups[groupName] = items;
		}
	}
	return o;
}