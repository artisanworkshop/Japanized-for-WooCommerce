/**
 * Shipment Settings Component
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	Button,
	SelectControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

const ShipmentSettings = ( {
	settings,
	updateSetting,
	saveSettings,
	saving,
} ) => {
	const [ timeZones, setTimeZones ] = useState( settings?.timeZones || [] );

	const handleSave = () => {
		// Ensure timeZones are included in the save
		const updatedSettings = {
			...settings,
			timeZones,
		};
		saveSettings( updatedSettings );
	};

	const addTimeZone = () => {
		setTimeZones( [ ...timeZones, { start_time: '', end_time: '' } ] );
	};

	const removeTimeZone = ( index ) => {
		const newTimeZones = timeZones.filter( ( _, i ) => i !== index );
		setTimeZones( newTimeZones );
	};

	const updateTimeZone = ( index, field, value ) => {
		const newTimeZones = [ ...timeZones ];
		newTimeZones[ index ][ field ] = value;
		setTimeZones( newTimeZones );
	};

	return (
		<div className="jp4wc-shipment-settings">
			<PanelBody
				title={ __(
					'Delivery date designation',
					'woocommerce-for-japan'
				) }
				initialOpen={ true }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Preferred delivery date',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check it if you want to use delivery date designation.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'delivery-date' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'delivery-date', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Preferred delivery date required',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check if delivery date is required field.',
							'woocommerce-for-japan'
						) }
						checked={
							settings?.[ 'delivery-date-required' ] === '1'
						}
						onChange={ ( value ) =>
							updateSetting(
								'delivery-date-required',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Start Date', 'woocommerce-for-japan' ) }
						help={ __(
							'Number of days from today to start accepting orders',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'start-date' ] || '0' }
						onChange={ ( value ) =>
							updateSetting( 'start-date', value )
						}
						min={ 0 }
					/>
				</PanelRow>

				<PanelRow>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Delivery Term', 'woocommerce-for-japan' ) }
						help={ __(
							'Number of days to accept delivery date',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'reception-period' ] || '14' }
						onChange={ ( value ) =>
							updateSetting( 'reception-period', value )
						}
						min={ 1 }
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Unspecified date description',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Text to display when date is not specified',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'unspecified-date' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'unspecified-date', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'Date format', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'date-format' ] || 'Y-m-d' }
						options={ [
							{ label: 'Y-m-d', value: 'Y-m-d' },
							{ label: 'Y年m月d日', value: 'Y年m月d日' },
							{ label: 'm/d/Y', value: 'm/d/Y' },
							{ label: 'd/m/Y', value: 'd/m/Y' },
						] }
						onChange={ ( value ) =>
							updateSetting( 'date-format', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Display day of week',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'day-of-week' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'day-of-week', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Delivery deadline',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Time format: HH:MM:SS (e.g., 14:00:00)',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'delivery-deadline' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'delivery-deadline', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<div style={ { width: '100%' } }>
						<h4>
							{ __(
								'No delivery weekday',
								'woocommerce-for-japan'
							) }
						</h4>
						<div
							style={ {
								display: 'flex',
								flexWrap: 'wrap',
								gap: '20px',
							} }
						>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Mon', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-mon' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-mon', value ? '1' : '' )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Tue', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-tue' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-tue', value ? '1' : '' )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Wed', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-wed' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-wed', value ? '1' : '' )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Thr', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-thu' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-thu', value ? '1' : '' )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Fri', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-fri' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-fri', value ? '1' : '' )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Sat', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-sat' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-sat', value ? '1' : '' )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Sun', 'woocommerce-for-japan' ) }
								checked={ settings?.[ 'no-sun' ] === '1' }
								onChange={ ( value ) =>
									updateSetting( 'no-sun', value ? '1' : '' )
								}
							/>
						</div>
					</div>
				</PanelRow>

				<PanelRow>
					<div style={ { width: '100%' } }>
						<h4>
							{ __( 'Holiday term', 'woocommerce-for-japan' ) }
						</h4>
						<div style={ { display: 'flex', gap: '10px' } }>
							<div style={ { flex: 1 } }>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom={ true }
									label={ __(
										'Holiday start date',
										'woocommerce-for-japan'
									) }
									type="date"
									value={
										settings?.[ 'holiday-start-date' ] || ''
									}
									onChange={ ( value ) =>
										updateSetting(
											'holiday-start-date',
											value
										)
									}
								/>
							</div>
							<div style={ { flex: 1 } }>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom={ true }
									label={ __(
										'Holiday end date',
										'woocommerce-for-japan'
									) }
									type="date"
									value={
										settings?.[ 'holiday-end-date' ] || ''
									}
									onChange={ ( value ) =>
										updateSetting(
											'holiday-end-date',
											value
										)
									}
								/>
							</div>
						</div>
					</div>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __(
					'Delivery Time designation',
					'woocommerce-for-japan'
				) }
				initialOpen={ true }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Delivery time zone',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check it if you want to use delivery time zone.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'delivery-time-zone' ] === '1' }
						onChange={ ( value ) =>
							updateSetting(
								'delivery-time-zone',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Delivery time zone required',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check if delivery time zone is required field.',
							'woocommerce-for-japan'
						) }
						checked={
							settings?.[ 'delivery-time-zone-required' ] === '1'
						}
						onChange={ ( value ) =>
							updateSetting(
								'delivery-time-zone-required',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Unspecified Time description',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Text to display when time is not specified',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'unspecified-time' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'unspecified-time', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<div style={ { width: '100%' } }>
						<h4>
							{ __(
								'Delivery time zone management',
								'woocommerce-for-japan'
							) }
						</h4>
						{ timeZones.map( ( zone, index ) => (
							<div
								key={ index }
								style={ {
									marginBottom: '10px',
									padding: '10px',
									border: '1px solid #ddd',
									display: 'flex',
									alignItems: 'flex-end',
									gap: '10px',
								} }
							>
								<div style={ { flex: 1 } }>
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom={ true }
										label={ __(
											'Start Time',
											'woocommerce-for-japan'
										) }
										value={ zone.start_time }
										onChange={ ( value ) =>
											updateTimeZone(
												index,
												'start_time',
												value
											)
										}
										placeholder="09:00"
									/>
								</div>
								<div style={ { flex: 1 } }>
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom={ true }
										label={ __(
											'End Time',
											'woocommerce-for-japan'
										) }
										value={ zone.end_time }
										onChange={ ( value ) =>
											updateTimeZone(
												index,
												'end_time',
												value
											)
										}
										placeholder="12:00"
									/>
								</div>
								<div style={ { paddingBottom: '8px' } }>
									<Button
										variant="secondary"
										isDestructive
										onClick={ () =>
											removeTimeZone( index )
										}
									>
										{ __(
											'Remove',
											'woocommerce-for-japan'
										) }
									</Button>
								</div>
							</div>
						) ) }
						<Button variant="secondary" onClick={ addTimeZone }>
							{ __( 'Add Time Zone', 'woocommerce-for-japan' ) }
						</Button>
					</div>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __(
					'Notification of missing required fields',
					'woocommerce-for-japan'
				) }
				initialOpen={ true }
			>
				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Notification email address',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Email address to notify when required delivery fields are missing',
							'woocommerce-for-japan'
						) }
						type="email"
						value={
							settings?.[ 'delivery-notification-email' ] || ''
						}
						onChange={ ( value ) =>
							updateSetting(
								'delivery-notification-email',
								value
							)
						}
					/>
				</PanelRow>
			</PanelBody>

			<div className="jp4wc-settings-footer">
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving...', 'woocommerce-for-japan' )
						: __( 'Save Settings', 'woocommerce-for-japan' ) }
				</Button>
			</div>
		</div>
	);
};

export default ShipmentSettings;
