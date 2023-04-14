import { __ } from '@wordpress/i18n';
import {useBlockProps} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import { Spinner } from '@wordpress/components';


const Edit = () => {

	const [html, setHtml] = useState(<Spinner />);

	useEffect( 
		() => {
			async function getHTML(){
				setHtml(<Spinner />);
				const statementHtml = await apiFetch({path: `${sim.restApiPrefix}/banking/get_statements`});
				setHtml(statementHtml);
			}
			getHTML();
		} , 
		[]
	);

	return (
		<>
			<div {...useBlockProps()}>
				{html}
			</div>
		</>
	);
}

export default Edit;
