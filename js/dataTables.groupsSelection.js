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
	function Group( name, items ) {
		if ( typeof( name ) === "undefined" ) name = "NoName";
		if ( typeof( items ) === "undefined" ) items = [];
		var group = {
			name: name,
			items: items,
			selectedItems: []
		};
		return group;
	}

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
		selectItem: function( item ) {
			var groups = this.getGroups( item );
			for( var i = 0; i < groups.length; i++ ) {
				var g = this.groups[groups[i]];
				g.selectedItems.push( item );
			}
		},
		deselectItem: function( item ) {
			var groups = this.getGroups( item );
			for( var i = 0; i < groups.length; i++ ) {
				var g = this.groups[groups[i]];
				var index = groups.indexOf( item );
				g.selectedItems.splice( index, 1 );
			}
		},
		getItems: function( groupName ) {
			var g = this.groups[groupName];
			return g.items;
		},
		getSelectedItemsInGroup: function( groupName ) {
			var g = this.groups[ groupName ];
			return g.selectItems;
		},
		addGroup: function( groupName, items ) {
			var g = new this.Group( groupName, items );
			this.groups[ groupName ] = g;
		}
	}
	return o;
}