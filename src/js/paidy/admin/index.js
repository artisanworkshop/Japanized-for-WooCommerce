import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';
import { OnBoardingStep } from './onboarding-step';
import './index.scss';


document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('paidy-admin-settings');
    if (container) {
        const root = createRoot(container);
        root.render(<OnBoardingStep />);
    }
});