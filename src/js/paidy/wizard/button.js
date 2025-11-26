import { Button } from '@wordpress/components';

export const NextButton = ( { onClick } ) => {
	return (
		<Button variant="primary" onClick={ onClick }>
			{ __( 'Next', 'woocommerce-for-japan' ) }
		</Button>
	);
};

export const SaveButton = ( { onClick } ) => {
	return (
		<Button variant="primary" onClick={ onClick }>
			{ __( 'Save', 'woocommerce-for-japan' ) }
		</Button>
	);
};
