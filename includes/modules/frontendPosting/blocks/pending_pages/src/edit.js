import { __ } from '@wordpress/i18n';
import {useBlockProps} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";

const Edit = () => {
	const [html, setHtml] = useState([]);

	useEffect( 
		() => {
			async function getHtml(){
				const response = await apiFetch({path: sim.restApiPrefix+'/frontendposting/pending_pages'});
				setHtml( response );
			}
			getHtml();
		} ,
		[]
	);

	return (
		<>
			<div {...useBlockProps()}>
				{wp.element.RawHTML( { children: html })}
			</div>
		</>
	);
}

export default Edit;
