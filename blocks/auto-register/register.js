( function () {
	const { registerBlockType, getBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, ToggleControl, TextControl, SelectControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	function buildAttributeControl( attrName, attrConfig, attributes, setAttributes ) {
		const label = attrConfig.label || attrName;
		const value = attributes[ attrName ] ?? attrConfig.default ?? '';

		if ( attrConfig.type === 'boolean' ) {
			return el( ToggleControl, {
				key: attrName,
				label,
				checked: !! value,
				onChange: ( checked ) => setAttributes( { [ attrName ]: checked } ),
			} );
		}

		if ( ! emptyEnum( attrConfig.enum ) ) {
			return el( SelectControl, {
				key: attrName,
				label,
				value: String( value ),
				options: attrConfig.enum.map( ( option ) => ( {
					label: String( option ),
					value: String( option ),
				} ) ),
				onChange: ( newValue ) => setAttributes( { [ attrName ]: newValue } ),
			} );
		}

		if ( attrConfig.type === 'integer' || attrConfig.type === 'number' ) {
			return el( TextControl, {
				key: attrName,
				label,
				type: 'number',
				value: value === '' ? '' : String( value ),
				onChange: ( newValue ) =>
					setAttributes( {
						[ attrName ]: newValue === '' ? '' : Number( newValue ),
					} ),
			} );
		}

		if ( attrConfig.type === 'array' || attrConfig.type === 'object' ) {
			return null;
		}

		return el( TextControl, {
			key: attrName,
			label,
			value: value === undefined || value === null ? '' : String( value ),
			onChange: ( newValue ) => setAttributes( { [ attrName ]: newValue } ),
		} );
	}

	function emptyEnum( enumValues ) {
		return Array.isArray( enumValues ) && enumValues.length > 0;
	}

	function registerAutoBlock( block ) {
		const existing = getBlockType( block.name );

		if ( existing && existing.edit ) {
			return;
		}

		if ( existing ) {
			wp.blocks.unregisterBlockType( block.name );
		}

		const attributeDefinitions = {};
		Object.entries( block.attributes || {} ).forEach( ( [ attrName, attrConfig ] ) => {
			attributeDefinitions[ attrName ] = {
				type: attrConfig.type || 'string',
				default: attrConfig.default ?? '',
			};

			if ( emptyEnum( attrConfig.enum ) ) {
				attributeDefinitions[ attrName ].enum = attrConfig.enum;
			}
		} );

		registerBlockType( block.name, {
			title: block.title,
			icon: block.icon || 'admin-generic',
			category: block.category || 'widgets',
			attributes: attributeDefinitions,
			edit( { attributes, setAttributes } ) {
				const controls = Object.entries( block.attributes || {} )
					.map( ( [ attrName, attrConfig ] ) =>
						buildAttributeControl(
							attrName,
							attrConfig,
							attributes,
							setAttributes
						)
					)
					.filter( Boolean );

				return el(
					Fragment,
					{},
					controls.length
						? el(
								InspectorControls,
								{},
								el( PanelBody, { title: __( 'Settings', 'tsjippy' ) }, controls )
						  )
						: null,
					el(
						'div',
						useBlockProps(),
						el( ServerSideRender, {
							block: block.name,
							attributes,
						} )
					)
				);
			},
			save() {
				return null;
			},
		} );
	}

	( window.tsjippyAutoRegisterBlocks || [] ).forEach( registerAutoBlock );

	// #region agent log
	fetch( 'http://127.0.0.1:7606/ingest/65213ea6-d059-40d4-b7e9-410ca2f639ca', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-Debug-Session-Id': '0a5746',
		},
		body: JSON.stringify( {
			sessionId: '0a5746',
			runId: 'post-fix',
			hypothesisId: 'D',
			location: 'auto-register/register.js',
			message: 'Auto-register blocks registered in editor',
			data: {
				configuredBlocks: ( window.tsjippyAutoRegisterBlocks || [] ).map(
					( item ) => item.name
				),
				registeredTsjippyBlocks: wp.blocks
					.getBlockTypes()
					.filter( ( item ) => item.name.indexOf( 'tsjippy' ) === 0 )
					.map( ( item ) => item.name ),
			},
			timestamp: Date.now(),
		} ),
	} ).catch( () => {} );
	// #endregion
} )();
