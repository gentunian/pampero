function Item( name, selected ) {
	if ( typeof( name ) === "undefined" ) name = "NoName";
	if ( typeof( selected ) === "undefined" ) selected = 0;
	
	var item = {
		name: name,
		selected: selected
	}

	return item;
}

function ItemsCollection( itemsNames ) {
	var o = {
		items: {},
		length: 0,
		selectedCount: function() {
			var selected = this.getSelected();
			return selected.length;
		},
		remove: function( itemName ) {
			try {
				delete this.items[ itemName ];
				this.length--;
			} catch(e) {

			}
		},
		add: function( mixed ) {
			var itemsNames = [];
			if (!( mixed instanceof Array ) && typeof mixed === "string" ) {
				itemsNames.push( mixed );
			} else if ( mixed instanceof Array ) {
				itemsNames = mixed;
			}
			for( var i = 0; i < itemsNames.length; i++ ) {
				var item = new Item( itemsNames[i] );
				this.items[ item.name ] = item;
			}
			this.length = itemsNames.length;
		},
		get: function( itemName ) {
			try {
				return this.items[ itemName ];
			} catch(e) {
				console.log( "[WRN]: The item '" + itemName + "' was not found in this collection." );
				return null;
			}
		},
		hasItem: function( itemName ) {
			try {
				return this.items.hasOwnProperty( itemName );
			} catch(e) {
				return false;
			}
		},
		isSelected: function( itemName ) {
			try {
				return this.items[ itemName ].selected;
			} catch( e ) {
				console.log( "[WRN]: A request for querying selection state for item '" + itemName + "' was made, but the item does not exist in this collection." );
				return false;
			}
		},
		select: function( itemName ) {
			try {
				this.items[ itemName ].selected = true;
			} catch( e ) {
				console.log( "[WRN]: A request for selecting " + itemName + " was made, but item does not exists in this collection." );
			}
		},
		selectAll: function() {
			for( var item in this.items ) {
				if ( this.items.hasOwnProperty( item )) {
					this.select( item );
				}
			}

			return this.items;
		},
		selectNone: function() {
			for( var item in this.items ) {
				if ( this.items.hasOwnProperty( item )) {
					this.deselect( item );
				}
			}

			return this.items;
		},
		deselect: function( itemName ) {
			try {
				this.items[ itemName ].selected = false;
			} catch( e ) {
				console.log( "[WRN]: A request for deselecting " + itemName + " was made, but item does not exists in this collection." );
			}
		},
		getSelected: function() {
			var selected = [];
			for( var itemName in this.items ) {
				if ( this.items.hasOwnProperty( itemName )) {
					var item = this.items[ itemName ];
					if ( item.selected ) selected.push( item );
				}
			}
			return selected;
		},
		getDeselected: function() {
			var deselected = [];
			for( var itemName in this.items ) {
				if ( this.items.hasOwnProperty( itemName )) {
					var item = this.items[ itemName ];
					if ( item.selected == 0 ) selected.push( item );
				}
			}
			return deselected;
		},
		toArray: function() {
			var items = [];
			for( var itemName in this.items ) {
				if ( this.items.hasOwnProperty( itemName )) {
					var item = this.items[ itemName ];
					items.push( item );
				}
			}
			return items;
		}
	}

	o.add( itemsNames );

	return o;
}

function Groups( groupsTable, itemsTable ) {

	var o = {
		groups: {},
		selecting: false,
		groupsTable: groupsTable,
		itemsTable: itemsTable,
		isSelecting: function() {
			return this.selecting;
		},
		remove: function( groupName ) {
			try {
				delete this.groups[ groupName ];
			} catch(e) {
				console.log( "[WRN]: Attempt to delete a non-existent group name." );	
			}
		},
		add: function( groupName, itemsNames ) {
			return this.groups[ groupName ] = new ItemsCollection( itemsNames );
		},
		get: function( groupName ) {
			try {
				return this.groups[ groupName ];
			} catch(e) {
				return null;
			}
		},
		getGroupsForItem: function( itemName ) {
			var groups = {};
			for( var groupName in this.groups ) {
				if ( this.groups.hasOwnProperty( groupName )) {
					if ( this.groups[ groupName ].hasItem( itemName )) {
						groups[ groupName ] = this.groups[ groupName ];
					}
				}
			}
			return groups;
		},
		select: function( groupName ) {
			console.log("Selecting: "+groupName);

			// Gets the ItemCollection under the name 'groupName'
			var group = this.groups[ groupName ];

			// Selects all items in the ItemCollection
			group.selectAll();

			// Gets all selected items from ItemCollection
			var items = group.toArray();

			// Gets the table instance where items are stored
			var table =  $( '#' + this.itemsTable ).dataTable();

			// Gets the TableTools instace for table 'table'

			var tt = TableTools.fnGetInstance( this.itemsTable );

			// Gets the table data where items are stored
			var data = table.fnGetData();

			// Iterates through item data from the table where items are stored
			for( var i = 0; i < data.length; i++ ) {

				// Compares if the items id matches any of the selected items name property
				var id = data[ i ][ 'id' ];
				for( var j = 0; j < items.length; j++ ) {

					// If the items id matches the selected items name property, select it in the view
					if ( items[ j ].name === id ) {

						// Gets the TR element based on the i-th position
						var tr = table.fnGetNodes( i );

						// Avoids callback being call again and again and again. For this to work
						// the method below should ask if we are selecting: groups.isSelecting()
						// if the method below will call some select*() methods from Groups.
						this.selecting = true;

						// Selects the TR
						tt.fnSelect( tr );

						this.selecting = false;
					}
				}
			}
		},
		deselect: function( groupName ) {
			console.log("Deselecting: "+groupName);

			// Gets the group groupName
			var group = this.groups[ groupName ];

			// Deselects all the items in the ItemCollection
			group.selectNone();
            
            // Gets all deselected items from ItemCollection
			var items = group.toArray();

			// Gets the table instance where items are stored
			var table =  $( '#' + this.itemsTable ).dataTable();

			// Gets the TableTools instace for table 'table'

			var tt = TableTools.fnGetInstance( this.itemsTable );

			// Gets the table data where items are stored
			var data = table.fnGetData();

			// Iterates through item data from the table where items are stored
			for( var i = 0; i < data.length; i++ ) {

				// Compares if the items id matches any of the selected items name property
				var id = data[ i ][ 'id' ];
				for( var j = 0; j < items.length; j++ ) {

					// If the items id matches the selected items name property, and is not
					// select by any group, select it in the view
					if ( items[ j ].name === id && !this.isItemSelected( items[ j ].name )) {

						// Gets the TR element based on the i-th position
						var tr = table.fnGetNodes( i );

                        // Avoids callback being call again and again and again. For this to work
						// the method below should ask if we are selecting: groups.isSelecting()
						// if the method below will call some select*() methods from Groups.
						this.selecting = true;

						// Selects the TR
						tt.fnDeselect( tr );

						this.selecting = false;
					}
				}
			}
		},
		selectItem: function( itemName ) {
			// In order to call a view method that invokes fnDeselect() we
			// retrieve the TableTools instance, the table, and the correspondent data
			var tt = TableTools.fnGetInstance( this.groupsTable );
			var table = $( '#' + this.groupsTable ).dataTable();
			var rowData = table.fnGetData();

			for( var groupName in this.groups ) {
				if ( this.groups.hasOwnProperty( groupName )) {

					// For each group, check wheter or not the item exists in it
					if ( this.groups[ groupName ].hasItem( itemName )) {

						// If the item exists, select the item from this group
						this.groups[ groupName ].select( itemName );

						// If the group has all items selected, we should select the group
						if ( this.groups[ groupName ].selectedCount() == this.groups[ groupName ].length ) {

							// We need to find the row index and call fnSelect() for that row
							for( var i = 0; i < rowData.length; i++ ) {
								var rowGroupName = rowData[ i ].name;

								if ( groupName === rowGroupName ) {
									console.log("we have a match in group: " +groupName + " for item " + itemName);
									this.selecting = true;
									tt.fnSelect( table.fnGetNodes( i ) );
									this.selecting = false;
								}
							}
						}
					}
				}
			}
		},
		deselectItem: function( itemName ) {
			// In order to call a view method that invokes fnDeselect() we
			// retrieve the TableTools instance, the table, and the correspondent data
			var tt = TableTools.fnGetInstance( this.groupsTable );
			var table = $( '#' + this.groupsTable ).dataTable();
			var rowData = table.fnGetData();

			for( var groupName in this.groups ) {
				if ( this.groups.hasOwnProperty( groupName )) {

					// For each group, check wheter or not the item exists in it
					if ( this.groups[ groupName ].hasItem( itemName )) {

						// If the item exists, deselect the item from this group
						this.groups[ groupName ].deselect( itemName );

						// We need to find the row index and call fnDeselect() for that row
						for( var i = 0; i < rowData.length; i++ ) {
							var rowGroupName = rowData[ i ].name;
							
							if ( groupName === rowGroupName ) {
								console.log("we have a match in group: " +groupName + " for item " + itemName);
								this.selecting = true;
								tt.fnDeselect( table.fnGetNodes( i ) );
								this.selecting = false;
							}
						}
					}
				}
			}
		},
		isItemSelected: function( itemName ) {
			var groups = this.getGroupsForItem( itemName );
			for( var groupName in groups ) {
				if ( groups.hasOwnProperty( groupName )) {
					if ( groups[ groupName ].isSelected( itemName )) {
						return true;
					}
				}
			}
			return false;
		},
		isSelected: function( groupName ) {
			var group = this.groups[ groupName ];
			return group.isSelected();
		}
	}
	return o;
}
