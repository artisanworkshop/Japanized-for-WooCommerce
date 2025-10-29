import { __ } from '@wordpress/i18n';
import {
    TextControl,
    RadioControl,
    CheckboxControl,
    TextareaControl,
} from '@wordpress/components';

const StoreNameTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Trade name / Store name', 'woocommerce-for-japan' ) }
            className="paidy-store-name"
            type="text"
            onChange={ onChange }
            value={ value }
        />
    );
};

const SiteNameTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Your EC site name', 'woocommerce-for-japan' ) }
            className="paidy-site-name"
            type="text"
            onChange={ onChange }
            value={ value }
        />
    );
}

const StoreUrlTextControl = ( { value } ) => {
    return (
        <div className="components-base-control">
            <label className="components-base-control__label">
                { __( 'Store URL', 'woocommerce-for-japan' ) }
            </label>
            <div className="paidy-readonly-field">
                { value }
            </div>
        </div>
    );
};

const RegistEmailTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Email address for registering Paidy', 'woocommerce-for-japan' ) }
            value={ value }
            onChange={ onChange }
        />
    );
}

const ContactPhoneTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Contact phone number', 'woocommerce-for-japan' ) }
            type="tel"
            onChange={ onChange }
            value={ value }
        />
    );
}

const RepresentativeLastNameTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Representative (last name)', 'woocommerce-for-japan' ) }
            onChange={ onChange }
            value={ value }
        />
    );
}

const RepresentativeFirstNameTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Representative (first name)', 'woocommerce-for-japan' ) }
            onChange={ onChange }
            value={ value }
        />
    );
}

const RepresentativeLastNameKanaTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Representative (last name kana)', 'woocommerce-for-japan' ) }
            help={ __( 'Please enter in full-width katakana.', 'woocommerce-for-japan' ) }
            value={ value }
            onChange={ onChange }
        />
    );
}

const RepresentativeFirstNameKanaTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Representative (first name kana)', 'woocommerce-for-japan' ) }
            help={ __( 'Please enter in full-width katakana.', 'woocommerce-for-japan' ) }
            value={ value }
            onChange={ onChange }
        />
    );
}

const RepresentativeDateOfBirthTextControl = ( { value, onChange } ) => {
    return (
        <TextControl
            label={ __( 'Representative\'s date of birth (Gregorian calendar)', 'woocommerce-for-japan' ) }
            help={ __( 'Please select a date from the calendar icon on the far right.', 'woocommerce-for-japan' ) }
            value={ value }
            type="date"
            onChange={ onChange }
        />
    );
}

const AnnualGrossValueRadioControl = ( { value, onChange } ) => {
    return (
        <RadioControl
            label={ __( 'Annual gross merchandise value', 'woocommerce-for-japan' ) }
            selected={ value }
            options={ [
                { label: __( 'Less than 10 million yen', 'woocommerce-for-japan' ), value: 'less-than-10-million-yen' },
                { label: __( '10 million yen or more', 'woocommerce-for-japan' ), value: '10-million-yen-or-more' },
            ] }
            onChange={ onChange }
        />
    );
};

const AveragePurchaseAmountRadioControl = ( { value, onChange } ) => {
    return (
        <RadioControl
            label={ __( 'Average purchase amount per order', 'woocommerce-for-japan' ) }
            selected={ value }
            options={ [
                { label: __( 'Less than 50,000 yen', 'woocommerce-for-japan' ), value: 'less-than-50000-yen' },
                { label: __( 'Over 50,000 yen', 'woocommerce-for-japan' ), value: '50000-yen-or-more' },
            ] }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey01RadioControl = ( { value, onChange } ) => {
    return (
        <RadioControl
            label={ __( 'Do you use two-step or two-factor authentication to prevent compromised accounts?', 'woocommerce-for-japan' ) }
            selected={ value }
            onChange={ onChange }
            options={[
                {
                    label: __( 'Yes', 'woocommerce-for-japan' ),
                    value: 'yes'
                },
                {
                    label: __( 'No', 'woocommerce-for-japan' ),
                    value: 'no'
                },
                {
                    label: __( 'Unknown', 'woocommerce-for-japan' ),
                    value: 'unknown'
                },
            ]}
        />
    );
};

const SecuritySurvey01TextControl = ( { value, onChange } ) => {
    return (
        <TextareaControl
            label={ __( 'If the answer is "no", please provide an alternative.', 'woocommerce-for-japan' ) }
            value={ value }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey11CheckControl = ( { value, onChange } ) => {
    return (
        <CheckboxControl
            label={ __( 'Restricting access from suspicious IP addresses', 'woocommerce-for-japan' ) }
            checked={ value }
            help={ __( 'This refers to restricting access from suspicious IP addresses, such as those from overseas, using firewalls, WAFs, apps, etc.', 'woocommerce-for-japan' ) }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey12CheckControl = ( { value, onChange } ) => {
    return (
        <CheckboxControl
            label={ __( 'Identity verification using two-factor authentication, etc.', 'woocommerce-for-japan' ) }
            checked={ value }
            help={ __( 'This refers to increasing security strength by combining multiple levels or elements of authentication, such as biometric authentication using fingerprints or faces, or email or SMS authentication, in addition to ID and password.', 'woocommerce-for-japan' ) }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey13CheckControl = ( { value, onChange } ) => {
    return (
        <CheckboxControl
            label={ __( 'Fraud detection system (Fraud service)', 'woocommerce-for-japan' ) }
            checked={ value }
            help={ __( 'It is a security technology that constantly monitors communications on the network and detects fraudulent transactions in advance, such as the use of credit cards by third parties or impersonation.', 'woocommerce-for-japan' ) }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey14CheckControl = ( { value, onChange } ) => {
    return (
        <CheckboxControl
            label={ __( 'Device fingerprinting, etc.', 'woocommerce-for-japan' ) }
            help={ __( 'This technology tracks online behavior by using the characteristics of the operating environment of the user\'s device as a kind of fingerprint.', 'woocommerce-for-japan' ) }
            checked={ value }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey10TextAreaControl = ( { value, onChange } ) => {
    return (
        <TextareaControl
            label={ __( 'If there is more than one question with a "yes" answer, please provide an alternative solution.', 'woocommerce-for-japan' ) }
            value={ value }
            onChange={ onChange }
        />
    );
};

const SecuritySurvey08RadioControl = ( { value, onChange } ) => {
    return (
        <RadioControl
            label={ __( 'We have not received any administrative penalties under the Specified Commercial Transactions Law in the past five years.', 'woocommerce-for-japan' ) }
            selected={ value }
            onChange={ onChange }
            options={[
                {
                    label: __( 'Yes', 'woocommerce-for-japan' ),
                    value: 'yes'
                },
                {
                    label: __( 'No', 'woocommerce-for-japan' ),
                    value: 'no'
                },
            ]}
        />
    );
};

const SecuritySurvey09RadioControl = ( { value, onChange } ) => {
    return (
        <RadioControl
            label={ __( 'I have never been sued in a civil lawsuit or received an unfavorable judgment based on actions that violated the Consumer Contract Act.', 'woocommerce-for-japan' ) }
            selected={ value }
            onChange={ onChange }
            options={[
                {
                    label: __( 'Yes', 'woocommerce-for-japan' ),
                    value: 'yes'
                },
                {
                    label: __( 'No', 'woocommerce-for-japan' ),
                    value: 'no'
                },
            ]}
        />
    );
};

export {
    StoreNameTextControl,
    SiteNameTextControl,
    StoreUrlTextControl,
    RegistEmailTextControl,
    ContactPhoneTextControl,
    RepresentativeLastNameTextControl,
    RepresentativeFirstNameTextControl,
    RepresentativeLastNameKanaTextControl,
    RepresentativeFirstNameKanaTextControl,
    RepresentativeDateOfBirthTextControl,
    AnnualGrossValueRadioControl,
    AveragePurchaseAmountRadioControl,
    SecuritySurvey01RadioControl,
    SecuritySurvey01TextControl,
    SecuritySurvey11CheckControl,
    SecuritySurvey12CheckControl,
    SecuritySurvey13CheckControl,
    SecuritySurvey14CheckControl,
    SecuritySurvey10TextAreaControl,
    SecuritySurvey08RadioControl,
    SecuritySurvey09RadioControl,
};