import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import Edit from './edit';
import metadata from './block.json';


registerBlockType( metadata.name, {
	icon: 'money',
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	save: () => null
} );
