import React, { useState, useEffect } from 'react';
import { render } from 'react-dom';
import { Button, Card, CardActions, CardContent, Typography, TextField, Alert } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { nonce, gitkitOptions } from '@gitkit';
import PluginUploaderForm from './components/plugin-uploader-form';
import './style.scss';

function App() {
	const [ resultMessage, setResultMessage ] = useState( '' );

	useEffect( () => {
		// if ( ! _.isEmpty( resultMessage ) ) {
		setTimeout( () => {
			setResultMessage( '' );
		}, 2000 );
		// }
	}, [ resultMessage ] );

	const onSubmit = ( e ) => {
		e.persist();
		e.preventDefault();

		const data = new FormData( e.target );
		const formObject = Object.fromEntries( data.entries() );

		const ajaxData = {
			nonce,
			action: 'gitkit_save_settings',
		};

		wp.ajax.post( { ...formObject, ...ajaxData } ).done( ( message ) => {
			setResultMessage( message );
		} ).fail( ( error ) => {
			// eslint-disable-next-line no-console
			console.log( error );
		} );
	};
	return (
		<div>
			{ resultMessage ? <Alert severity="success">{ resultMessage }</Alert> : '' }
			<Card sx={ { minWidth: 275 } }>
				<form onSubmit={ onSubmit } >
					<CardContent>
						<Typography variant="h5" component="div">
							Settings
						</Typography>
						<br />
						<br />
						<div>
							<TextField
								id="outlined-helperText"
								className="gitkit-github-access-token"
								label="Github personal access token (classic)"
								defaultValue={ gitkitOptions.gitkit_github_access_token }
								helperText={ <a target="_blank" href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token#creating-a-personal-access-token-classic">Github access token</a> }
								type="password"
								name="gitkit_github_access_token"
							/>
						</div>
					</CardContent>
					<br />
					<br />
					<br />
					<CardActions sx={ { display: 'flex', justifyContent: 'center' } }>
						<Button variant="contained" color="success" type="submit">
							{ __( 'Save settings', 'gitkit' ) }
						</Button>
					</CardActions>
				</form>
			</Card>
		</div>
	);
}

if ( ! _.isNull( document.getElementById( 'gitkit-root' ) ) ) {
	render( <App />, document.getElementById( 'gitkit-root' ) );
}

function UploadPluginApp() {
	return (
		<>
			<PluginUploaderForm />
		</>
	);
}

if ( ! _.isNull( document.getElementById( 'gitkit-upload-plugin' ) ) ) {
	render( <UploadPluginApp />, document.getElementById( 'gitkit-upload-plugin' ) );
}
