/**
 * Free security check of wc.artws.info (issue #260).
 *
 * Shows every value the check would send, asks for the consent, runs the
 * check, shows its report and deletes its data. Nothing is sent until the
 * store manager agrees and clicks the button: the preview comes from
 * GET /jp4wc/v1/security/scan, which builds it with the same code as the
 * request and sends nothing.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	PanelBody,
	Button,
	CheckboxControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import {
	useState,
	useEffect,
	useRef,
	createInterpolateElement,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { dateI18n, getSettings } from '@wordpress/date';

import {
	REQUIREMENT_ORDER,
	checkLabels,
	requirementLabels,
	severityLabels,
} from './security-labels';

const PATH = '/jp4wc/v1/security/scan';

/**
 * Class of the body while the report is printed (see Settings.scss).
 */
const PRINT_CLASS = 'jp4wc-print-scan-report';

/**
 * Formats a time for display in the site's date and time format.
 *
 * @param {number|string} value Unix time in seconds, or an ISO 8601 string.
 * @return {string} Formatted time.
 */
const formatTime = ( value ) =>
	dateI18n(
		getSettings().formats.datetime,
		typeof value === 'number' ? new Date( value * 1000 ) : value
	);

/**
 * Returns the label of a revenue band.
 *
 * @param {string} band B0 to B5.
 * @return {string} Label.
 */
const revenueBandLabel = ( band ) =>
	( {
		B0: __( 'No revenue', 'woocommerce-for-japan' ),
		B1: __( 'Up to 1 million yen', 'woocommerce-for-japan' ),
		B2: __( 'Up to 10 million yen', 'woocommerce-for-japan' ),
		B3: __( 'Up to 50 million yen', 'woocommerce-for-japan' ),
		B4: __( 'Up to 100 million yen', 'woocommerce-for-japan' ),
		B5: __( 'Over 100 million yen', 'woocommerce-for-japan' ),
	}[ band ] || band );

/**
 * Returns the label of an order band.
 *
 * @param {string} band O0 to O4.
 * @return {string} Label.
 */
const orderBandLabel = ( band ) =>
	( {
		O0: __( 'No orders', 'woocommerce-for-japan' ),
		O1: __( 'Up to 100 orders', 'woocommerce-for-japan' ),
		O2: __( 'Up to 1,000 orders', 'woocommerce-for-japan' ),
		O3: __( 'Up to 10,000 orders', 'woocommerce-for-japan' ),
		O4: __( 'Over 10,000 orders', 'woocommerce-for-japan' ),
	}[ band ] || band );

/**
 * Returns the label of the status of a local check.
 *
 * @param {string} status pass, fail or unknown.
 * @return {string} Label.
 */
const checkStatusLabel = ( status ) => {
	switch ( status ) {
		case 'pass':
			return __( 'Passed', 'woocommerce-for-japan' );
		case 'fail':
			return __( 'Action needed', 'woocommerce-for-japan' );
		default:
			return __( 'Unconfirmed', 'woocommerce-for-japan' );
	}
};

/**
 * The list of every value sent, built from the payload the server would send.
 *
 * @param {Object}  props            Props.
 * @param {Object}  props.scan       State of the free check.
 * @param {boolean} props.bands      Whether the bands are asked for.
 * @param {boolean} props.loading    Whether the preview is being loaded again.
 * @param {boolean} props.registered Whether the site token is sent.
 * @return {Element} Element.
 */
const SentData = ( { scan, bands, loading, registered } ) => {
	const payload = scan.payload;
	let bandsValue = __( 'Not sent', 'woocommerce-for-japan' );
	if ( bands && loading ) {
		bandsValue = __( 'Counting…', 'woocommerce-for-japan' );
	} else if ( bands && payload.revenue_band ) {
		bandsValue = sprintf(
			/* translators: 1: revenue band code, 2: its label, 3: order band code, 4: its label */
			__( '%1$s (%2$s), %3$s (%4$s)', 'woocommerce-for-japan' ),
			payload.revenue_band,
			revenueBandLabel( payload.revenue_band ),
			payload.order_band,
			orderBandLabel( payload.order_band )
		);
	} else if ( bands && scan.bands_error ) {
		bandsValue = __(
			'Could not be counted, so they are not sent',
			'woocommerce-for-japan'
		);
	}

	const rows = [
		[ __( 'Site address', 'woocommerce-for-japan' ), payload.site_url ],
		[ __( 'WordPress version', 'woocommerce-for-japan' ), payload.wp_ver ],
		[
			__( 'WooCommerce version', 'woocommerce-for-japan' ),
			payload.wc_ver,
		],
		[ __( 'PHP version', 'woocommerce-for-japan' ), payload.php_ver ],
		[
			__( 'Version of this plugin', 'woocommerce-for-japan' ),
			payload.jp4wc_ver,
		],
		[ __( 'Language', 'woocommerce-for-japan' ), payload.locale ],
	];

	return (
		<table className="widefat striped jp4wc-scan-sent">
			<tbody>
				{ rows.map( ( [ label, value ] ) => (
					<tr key={ label }>
						<th scope="row">{ label }</th>
						<td>
							<code>{ value }</code>
						</td>
					</tr>
				) ) }
				<tr>
					<th scope="row">
						{ __( 'Plugins and themes', 'woocommerce-for-japan' ) }
					</th>
					<td>
						<details>
							<summary>
								{ sprintf(
									/* translators: %d: number of plugins and themes */
									__(
										'%d installed, active or not: type, slug, version and whether it is active',
										'woocommerce-for-japan'
									),
									payload.software.length
								) }
							</summary>
							<table className="widefat jp4wc-scan-software">
								<thead>
									<tr>
										<th scope="col">
											{ __(
												'Type',
												'woocommerce-for-japan'
											) }
										</th>
										<th scope="col">
											{ __(
												'Slug',
												'woocommerce-for-japan'
											) }
										</th>
										<th scope="col">
											{ __(
												'Version',
												'woocommerce-for-japan'
											) }
										</th>
										<th scope="col">
											{ __(
												'Active',
												'woocommerce-for-japan'
											) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ payload.software.map(
										( software, index ) => (
											<tr key={ index }>
												<td>
													{ software.type === 'theme'
														? __(
																'Theme',
																'woocommerce-for-japan'
														  )
														: __(
																'Plugin',
																'woocommerce-for-japan'
														  ) }
												</td>
												<td>
													<code>
														{ software.slug }
													</code>
												</td>
												<td>
													<code>
														{ software.version }
													</code>
												</td>
												<td>
													{ software.active
														? __(
																'Yes',
																'woocommerce-for-japan'
														  )
														: __(
																'No',
																'woocommerce-for-japan'
														  ) }
												</td>
											</tr>
										)
									) }
								</tbody>
							</table>
						</details>
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __(
							'Results of the checks',
							'woocommerce-for-japan'
						) }
					</th>
					<td>
						<details>
							<summary>
								{ sprintf(
									/* translators: %d: number of checks */
									__(
										'%d checks: ID and result only, never the settings behind them',
										'woocommerce-for-japan'
									),
									payload.local_findings.length
								) }
							</summary>
							<ul className="jp4wc-scan-findings-sent">
								{ payload.local_findings.map( ( finding ) => (
									<li key={ finding.check_id }>
										<code>{ finding.check_id }</code>{ ' ' }
										{ checkStatusLabel( finding.status ) }
									</li>
								) ) }
							</ul>
						</details>
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __(
							'Revenue and order bands',
							'woocommerce-for-japan'
						) }
					</th>
					<td>{ bandsValue }</td>
				</tr>
				<tr>
					<th scope="row">
						{ __(
							'Version of the consent text',
							'woocommerce-for-japan'
						) }
					</th>
					<td>
						<code>{ payload.consent_version }</code>
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __( 'Format of the data', 'woocommerce-for-japan' ) }
					</th>
					<td>
						<code>{ payload.schema_version }</code>
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __(
							'Site identification token',
							'woocommerce-for-japan'
						) }
					</th>
					<td>
						{ registered
							? __(
									'Sent: the token wc.artws.info issued to this site on its first check. Its value is not shown.',
									'woocommerce-for-japan'
							  )
							: __(
									'Not sent with the first check: wc.artws.info issues it then, and it is sent with later checks and with the deletion. Its value is not shown.',
									'woocommerce-for-japan'
							  ) }
					</td>
				</tr>
			</tbody>
		</table>
	);
};

/**
 * The report of the last check.
 *
 * Every string comes from wc.artws.info and is shown as text.
 *
 * @param {Object} props           Props.
 * @param {Object} props.report    Report.
 * @param {number} props.scannedAt Unix time of the check.
 * @return {Element} Element.
 */
const ScanReport = ( { report, scannedAt } ) => {
	const names = requirementLabels();
	const severities = severityLabels();

	const print = () => {
		const cleanUp = () => document.body.classList.remove( PRINT_CLASS );
		document.body.classList.add( PRINT_CLASS );
		window.addEventListener( 'afterprint', cleanUp, { once: true } );
		window.print();
	};

	return (
		<div className="jp4wc-scan-report">
			<div className="jp4wc-scan-report-header">
				<h4>
					{ __(
						'Report of the free security check',
						'woocommerce-for-japan'
					) }
				</h4>
				<Button
					variant="secondary"
					onClick={ print }
					className="jp4wc-scan-report-print"
				>
					{ __( 'Save as PDF', 'woocommerce-for-japan' ) }
				</Button>
			</div>
			<p className="description">
				{ sprintf(
					/* translators: %s: date and time of the check */
					__( 'Checked: %s', 'woocommerce-for-japan' ),
					formatTime( scannedAt )
				) }
				{ report.generated_at &&
					' / ' +
						sprintf(
							/* translators: %s: date and time the report was made */
							__( 'Report made: %s', 'woocommerce-for-japan' ),
							formatTime( report.generated_at )
						) }
			</p>

			<div className="jp4wc-security-scorecard">
				<div className="jp4wc-security-total">
					<span className="jp4wc-security-total-label">
						{ __( 'Total score', 'woocommerce-for-japan' ) }
					</span>
					<span className="jp4wc-security-total-score">
						{ report.score.total }
						<small> / 100</small>
					</span>
				</div>
				<ul className="jp4wc-security-requirements">
					{ REQUIREMENT_ORDER.map( ( key ) => (
						<li key={ key } className="jp4wc-security-requirement">
							<span className="jp4wc-security-requirement-name">
								{ names[ key ] }
							</span>
							<span className="jp4wc-security-requirement-score">
								{ report.score[ key ] }
								<small> / 100</small>
							</span>
						</li>
					) ) }
				</ul>
			</div>

			<h5>{ __( 'Items that need action', 'woocommerce-for-japan' ) }</h5>
			{ report.findings.length === 0 ? (
				<p>
					{ __( 'No items need action.', 'woocommerce-for-japan' ) }
				</p>
			) : (
				<ol className="jp4wc-scan-report-findings">
					{ report.findings.map( ( finding, index ) => (
						<li key={ index }>
							<p className="jp4wc-scan-report-title">
								<span
									className={ `jp4wc-security-severity is-${ finding.severity }` }
								>
									{ severities[ finding.severity ] ||
										finding.severity }
								</span>{ ' ' }
								<strong>{ finding.title }</strong>
							</p>
							{ finding.software && (
								<p className="description">
									<code>{ finding.software.slug }</code>{ ' ' }
									{ finding.software.version }
								</p>
							) }
							{ finding.detail && <p>{ finding.detail }</p> }
							{ finding.impact && (
								<p>
									{ sprintf(
										/* translators: %s: what can happen */
										__(
											'Impact: %s',
											'woocommerce-for-japan'
										),
										finding.impact
									) }
								</p>
							) }
							{ finding.fix.length > 0 && (
								<ol className="jp4wc-scan-report-fix">
									{ finding.fix.map( ( step, stepIndex ) => (
										<li key={ stepIndex }>{ step }</li>
									) ) }
								</ol>
							) }
							{ finding.reference && (
								<p>
									<a
										href={ finding.reference }
										target="_blank"
										rel="noopener noreferrer"
									>
										{ __(
											'Details',
											'woocommerce-for-japan'
										) }
									</a>
								</p>
							) }
						</li>
					) ) }
				</ol>
			) }

			{ report.next_steps.length > 0 && (
				<>
					<h5>{ __( 'Next steps', 'woocommerce-for-japan' ) }</h5>
					<ul className="jp4wc-scan-report-steps">
						{ report.next_steps.map( ( step, index ) => (
							<li key={ index }>{ step }</li>
						) ) }
					</ul>
				</>
			) }

			{ report.attribution && (
				<p className="description">{ report.attribution }</p>
			) }
		</div>
	);
};

/**
 * Returns the key of a known vulnerability: the affected software.
 *
 * @param {string} type plugin, theme or core.
 * @param {string} slug Slug.
 * @return {string} Key.
 */
const vulnerabilityKey = ( type, slug ) =>
	type === 'core' ? 'V-01:core' : `V-01:${ type }:${ slug }`;

/**
 * Compares the report with the checks above.
 *
 * Both sides are keyed alike: V-01 by the affected software (the report
 * holds one item per vulnerability record), the other checks by their ID.
 *
 * The report holds findings only, so a check the checks above could not
 * confirm cannot be compared: it is returned on its own.
 *
 * @param {Object} report Report.
 * @param {Object} view   Result of GET /jp4wc/v1/security/findings.
 * @return {{onlyReport: string[], onlyLocal: string[], unconfirmed: string[]}}
 *         Names of the items that need action on one side only, and of the
 *         checks above that could not be confirmed.
 */
const compareWithLocal = ( report, view ) => {
	const checks = checkLabels();
	const local = new Map();
	const unconfirmed = [];
	view.checks.forEach( ( check ) => {
		if ( check.status === 'unknown' ) {
			unconfirmed.push(
				checks[ check.check_id ]?.title || check.check_id
			);
			return;
		}
		if ( check.status !== 'fail' ) {
			return;
		}
		const items =
			check.check_id === 'V-01' ? view.vulnerabilities?.items || [] : [];
		if ( items.length === 0 ) {
			local.set(
				check.check_id,
				checks[ check.check_id ]?.title || check.check_id
			);
			return;
		}
		items.forEach( ( item ) =>
			local.set(
				vulnerabilityKey( item.type, item.slug ),
				sprintf(
					/* translators: 1: plugin, theme or WordPress name, 2: its version */
					__(
						'Known vulnerabilities in %1$s %2$s',
						'woocommerce-for-japan'
					),
					item.name,
					item.version
				)
			)
		);
	} );

	const remote = new Map();
	report.findings.forEach( ( finding ) => {
		const key =
			finding.check_id === 'V-01' && finding.software
				? vulnerabilityKey(
						finding.software.type,
						finding.software.slug
				  )
				: finding.check_id;
		if ( ! remote.has( key ) ) {
			remote.set( key, finding.title );
		}
	} );

	const only = ( side, other ) =>
		[ ...side ]
			.filter( ( [ key ] ) => ! other.has( key ) )
			.map( ( [ , name ] ) => name );
	return {
		onlyReport: only( remote, local ),
		onlyLocal: only( local, remote ),
		unconfirmed,
	};
};

/**
 * What the report adds to the checks above.
 *
 * Kept out of the report, so that "Save as PDF" leaves it out.
 *
 * @param {Object} props           Props.
 * @param {Object} props.report    Report.
 * @param {number} props.scannedAt Unix time of the check.
 * @param {Object} props.view      Result of the checks above.
 * @return {Element|null} Element.
 */
const LocalComparison = ( { report, scannedAt, view } ) => {
	if ( view.checks.length === 0 ) {
		return null;
	}
	const { onlyReport, onlyLocal, unconfirmed } = compareWithLocal(
		report,
		view
	);

	return (
		<div className="jp4wc-scan-comparison">
			<h4>
				{ __(
					'Compared with the checks above',
					'woocommerce-for-japan'
				) }
			</h4>
			{ onlyReport.length === 0 && onlyLocal.length === 0 ? (
				<p>
					{ unconfirmed.length === 0
						? __(
								'Checked again against the latest vulnerability data on wc.artws.info, the result is the same as the checks above. The report below adds what each item means, what can happen and how to fix it, and can be saved as a PDF.',
								'woocommerce-for-japan'
						  )
						: __(
								'Apart from the items the checks above could not confirm, the result checked again against the latest vulnerability data on wc.artws.info is the same as the checks above. The report below adds what each item means, what can happen and how to fix it, and can be saved as a PDF.',
								'woocommerce-for-japan'
						  ) }
				</p>
			) : (
				<>
					{ onlyReport.length > 0 && (
						<>
							<p>
								<strong>
									{ __(
										'Found only by wc.artws.info',
										'woocommerce-for-japan'
									) }
								</strong>
							</p>
							<ul>
								{ onlyReport.map( ( name, index ) => (
									<li key={ index }>{ name }</li>
								) ) }
							</ul>
						</>
					) }
					{ onlyLocal.length > 0 && (
						<>
							<p>
								<strong>
									{ __(
										'Found only by the checks above',
										'woocommerce-for-japan'
									) }
								</strong>
							</p>
							<ul>
								{ onlyLocal.map( ( name, index ) => (
									<li key={ index }>{ name }</li>
								) ) }
							</ul>
						</>
					) }
					{ view.checked_at > scannedAt && (
						<p className="description">
							{ sprintf(
								/* translators: 1: date and time of the checks above, 2: date and time of the free check */
								__(
									'The checks above are from %1$s and this check from %2$s. Updates and setting changes made in between also show as differences.',
									'woocommerce-for-japan'
								),
								formatTime( view.checked_at ),
								formatTime( scannedAt )
							) }
						</p>
					) }
				</>
			) }
			{ unconfirmed.length > 0 && (
				<>
					<p>
						<strong>
							{ __(
								'Could not be confirmed by the checks above',
								'woocommerce-for-japan'
							) }
						</strong>
					</p>
					<ul>
						{ unconfirmed.map( ( name, index ) => (
							<li key={ index }>{ name }</li>
						) ) }
					</ul>
				</>
			) }
		</div>
	);
};

/**
 * The free security check.
 *
 * The values sent and the consent open from a button, and once the site has
 * agreed, the values open from a toggle under the report.
 *
 * @param {Object} props           Props.
 * @param {number} props.checkedAt When the local checks last ran: the preview
 *                                 is loaded again when they run again.
 * @param {Object} props.view      Result of the checks above, which the
 *                                 report is compared with.
 * @return {Element} Element.
 */
const SecurityScan = ( { checkedAt, view } ) => {
	const [ scan, setScan ] = useState( null );
	const [ loadError, setLoadError ] = useState( null );
	const [ loadingBands, setLoadingBands ] = useState( false );
	const [ agreed, setAgreed ] = useState( false );
	const [ bands, setBands ] = useState( false );
	// idle, running, done, reregistered, confirm-delete, deleting, deleted or error.
	const [ action, setAction ] = useState( { state: 'idle' } );
	// Whether the values sent (and before the consent, the consent) are shown.
	const [ open, setOpen ] = useState( false );
	const mounted = useRef( true );
	const bandsChosen = useRef( false );
	// Number of the last state asked for: the answer to an older request is dropped.
	const loadCount = useRef( 0 );

	/**
	 * Shows a state the server sent, in place of any request still running.
	 *
	 * @param {Object} response State of the free check.
	 */
	const show = ( response ) => {
		loadCount.current++;
		setScan( response );
		setLoadError( null );
		setLoadingBands( false );
	};

	const reportLoadError = ( err ) => {
		if ( mounted.current ) {
			setLoadError(
				err.message ||
					__(
						'The free check could not be loaded.',
						'woocommerce-for-japan'
					)
			);
		}
	};

	/**
	 * Loads the state, with the bands in the preview or not.
	 *
	 * Every load goes through here, so that the bands sent are those the
	 * preview shows: the check cannot run until the latest load is done, and
	 * when it fails the bands are not sent.
	 *
	 * @param {boolean} value Whether the bands are sent.
	 * @return {Promise<Object|null>} The state, or null when it failed or a
	 *                                later request replaced it.
	 */
	const loadBands = async ( value ) => {
		const number = ++loadCount.current;
		const current = () => mounted.current && number === loadCount.current;
		setBands( value );
		setLoadingBands( true );
		let response;
		try {
			response = await apiFetch( {
				path: value ? `${ PATH }?bands=1` : PATH,
			} );
		} catch ( err ) {
			if ( current() ) {
				setBands( false );
				setLoadingBands( false );
				reportLoadError( err );
			}
			return null;
		}
		if ( ! current() ) {
			return null;
		}
		setScan( response );
		setLoadError( null );
		setLoadingBands( false );
		return response;
	};

	useEffect( () => {
		mounted.current = true;
		return () => {
			mounted.current = false;
		};
	}, [] );

	useEffect( () => {
		// Until the bands are chosen on this screen, those of the last check are offered.
		const restore = ! bandsChosen.current && ! bands;
		( async () => {
			const response = await loadBands( bands );
			if (
				restore &&
				response &&
				! bandsChosen.current &&
				response.bands_available &&
				response.consent?.current &&
				response.consent.bands
			) {
				await loadBands( true );
			}
		} )();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ checkedAt ] );

	const toggleBands = ( value ) => {
		bandsChosen.current = true;
		loadBands( value );
	};

	const run = async () => {
		setAction( { state: 'running' } );
		try {
			const response = await apiFetch( {
				path: PATH,
				method: 'POST',
				// Only the consent box agrees: a screen opened before the consent was
				// withdrawn or the text changed leaves it to the stored consent.
				data: { consent: ! consented && agreed, bands },
			} );
			if ( ! mounted.current ) {
				return;
			}
			show( response );
			setAgreed( false );
			setOpen( false );
			setAction( {
				state: response.reregistered ? 'reregistered' : 'done',
			} );
		} catch ( err ) {
			if ( ! mounted.current ) {
				return;
			}
			setAction( {
				state: 'error',
				message:
					err.message ||
					__(
						'The check could not be run.',
						'woocommerce-for-japan'
					),
				reset: err.data?.registration_reset === true,
			} );
			// The consent, the token or the report may have changed.
			loadBands( bands );
		}
	};

	const remove = async () => {
		setAction( { state: 'deleting' } );
		try {
			const response = await apiFetch( {
				path: PATH,
				method: 'DELETE',
			} );
			if ( ! mounted.current ) {
				return;
			}
			show( response );
			setAgreed( false );
			setBands( false );
			bandsChosen.current = false;
			setOpen( false );
			setAction( { state: 'deleted' } );
		} catch ( err ) {
			if ( ! mounted.current ) {
				return;
			}
			setAction( {
				state: 'error',
				message:
					err.message ||
					__(
						'The check data could not be deleted.',
						'woocommerce-for-japan'
					),
			} );
			loadBands( bands );
		}
	};

	const busy = action.state === 'running' || action.state === 'deleting';
	const consented = scan?.consent?.current === true;
	// Whether the bands are sent is asked on every check, so once the site has
	// agreed the box stays in view beside "Run the check again".
	const bandsControl = scan?.bands_available && (
		<CheckboxControl
			__nextHasNoMarginBottom={ true }
			label={ __(
				'Also send the revenue and order bands of the last 12 months (optional)',
				'woocommerce-for-japan'
			) }
			help={ __(
				'Used to show which measures to take first for the size of the store. Only the band codes are sent, never the amounts.',
				'woocommerce-for-japan'
			) }
			checked={ bands }
			onChange={ toggleBands }
			disabled={ busy }
		/>
	);
	const hasData =
		scan &&
		( scan.consent || scan.registered || scan.other_site || scan.report );
	const dismiss = () => setAction( { state: 'idle' } );

	return (
		<PanelBody
			title={ __(
				'Free security check by wc.artws.info',
				'woocommerce-for-japan'
			) }
			initialOpen={ true }
			className="jp4wc-scan"
		>
			<p>
				{ __(
					'Send the information below to wc.artws.info, operated by 職人工房 合同会社, and get a report on this site: the installed software checked against the latest vulnerability data on the server, the results of the checks above, and what to do first. Nothing is sent until you agree and click the button.',
					'woocommerce-for-japan'
				) }
			</p>

			{ ! scan && ! loadError && <Spinner /> }
			{ loadError && (
				<Notice status="error" isDismissible={ false }>
					{ loadError }
				</Notice>
			) }

			{ action.state === 'running' && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Running the check on wc.artws.info…',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }
			{ action.state === 'done' && (
				<Notice status="success" onRemove={ dismiss }>
					{ __(
						'The check has finished. The report is below.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }
			{ action.state === 'reregistered' && (
				<Notice status="warning" onRemove={ dismiss }>
					{ __(
						'This site was registered with wc.artws.info again, so earlier results can no longer be shown. wc.artws.info deletes them after 180 days.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }
			{ action.state === 'deleted' && (
				<Notice status="success" onRemove={ dismiss }>
					{ __(
						'The check data was deleted from wc.artws.info and from this site.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }
			{ action.state === 'error' && (
				<Notice status="error" onRemove={ dismiss }>
					{ action.message }
				</Notice>
			) }
			{ action.state === 'error' && action.reset && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'wc.artws.info no longer accepts the earlier registration of this site, so earlier results can no longer be shown. The next check registers this site again. wc.artws.info deletes the earlier results after 180 days.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }

			{ scan?.report && view && (
				<LocalComparison
					report={ scan.report }
					scannedAt={ scan.scanned_at }
					view={ view }
				/>
			) }
			{ scan?.report && (
				<ScanReport
					report={ scan.report }
					scannedAt={ scan.scanned_at }
				/>
			) }

			{ scan && ! scan.enabled && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'The free check sends the results of the security check: turn the security check on and save the settings to use it.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }
			{ scan && scan.enabled && ! scan.ready && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'The free check sends the results of the security check, which has not run yet. It can be used once the results appear above.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }

			{ scan && consented && (
				<>
					{ bandsControl }
					<div className="jp4wc-scan-actions">
						<Button
							variant="primary"
							onClick={ run }
							isBusy={ action.state === 'running' }
							disabled={ busy || loadingBands || ! scan.ready }
						>
							{ __(
								'Run the check again',
								'woocommerce-for-japan'
							) }
						</Button>
						<span className="description">
							{ sprintf(
								/* translators: %s: date and time of the consent */
								__(
									'You agreed to the Terms of Use and the Privacy Policy on %s.',
									'woocommerce-for-japan'
								),
								formatTime( scan.consent.consented_at )
							) }
						</span>
					</div>
					<Button
						variant="link"
						onClick={ () => setOpen( ! open ) }
						aria-expanded={ open }
					>
						{ open
							? __(
									'Hide the information sent',
									'woocommerce-for-japan'
							  )
							: __(
									'Show the information sent',
									'woocommerce-for-japan'
							  ) }
					</Button>
				</>
			) }
			{ scan && ! consented && ! open && (
				<div className="jp4wc-scan-actions">
					<Button
						variant="primary"
						onClick={ () => setOpen( true ) }
						aria-expanded={ false }
						disabled={ busy || ! scan.ready }
					>
						{ __( 'Get the free check', 'woocommerce-for-japan' ) }
					</Button>
				</div>
			) }

			{ scan && open && (
				<div className="jp4wc-scan-consent">
					<h4>
						{ __(
							'Information sent to wc.artws.info',
							'woocommerce-for-japan'
						) }
					</h4>
					<SentData
						scan={ scan }
						bands={ bands }
						loading={ loadingBands }
						registered={ scan.registered }
					/>
					<p>
						{ __(
							'The administrator e-mail address, customer information, order amounts, API keys, the contents of wp-config.php and server paths are not sent. wc.artws.info keeps the data for 180 days.',
							'woocommerce-for-japan'
						) }
					</p>

					{ ! consented && bandsControl }

					<p>
						{ createInterpolateElement(
							__(
								'Read the <terms>Terms of Use</terms> and the <privacy>Privacy Policy</privacy> of the free check.',
								'woocommerce-for-japan'
							),
							{
								terms: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content -- createInterpolateElement() puts the translated words inside.
									<a
										href={ scan.terms_url }
										target="_blank"
										rel="noopener noreferrer"
									/>
								),
								privacy: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content -- createInterpolateElement() puts the translated words inside.
									<a
										href={ scan.privacy_url }
										target="_blank"
										rel="noopener noreferrer"
									/>
								),
							}
						) }
					</p>

					{ ! consented && (
						<>
							<CheckboxControl
								__nextHasNoMarginBottom={ true }
								label={ __(
									'I agree to the Terms of Use and the Privacy Policy, and send the information above.',
									'woocommerce-for-japan'
								) }
								checked={ agreed }
								onChange={ setAgreed }
								disabled={ busy || ! scan.ready }
							/>
							<div className="jp4wc-scan-actions">
								<Button
									variant="primary"
									onClick={ run }
									isBusy={ action.state === 'running' }
									disabled={
										busy ||
										loadingBands ||
										! scan.ready ||
										! agreed
									}
								>
									{ __(
										'Agree and run the check',
										'woocommerce-for-japan'
									) }
								</Button>
								<Button
									variant="tertiary"
									onClick={ () => {
										setOpen( false );
										setAgreed( false );
									} }
									disabled={ busy }
								>
									{ __( 'Cancel', 'woocommerce-for-japan' ) }
								</Button>
							</div>
						</>
					) }
				</div>
			) }

			{ hasData && (
				<div className="jp4wc-scan-delete">
					{ action.state === 'confirm-delete' ? (
						<>
							<p>
								{ __(
									"This deletes this site's data on wc.artws.info, and the consent and the report kept on this site.",
									'woocommerce-for-japan'
								) }
							</p>
							<Button
								variant="primary"
								isDestructive
								onClick={ remove }
							>
								{ __( 'Delete', 'woocommerce-for-japan' ) }
							</Button>{ ' ' }
							<Button variant="tertiary" onClick={ dismiss }>
								{ __( 'Cancel', 'woocommerce-for-japan' ) }
							</Button>
						</>
					) : (
						<Button
							variant="secondary"
							isDestructive
							onClick={ () =>
								setAction( { state: 'confirm-delete' } )
							}
							isBusy={ action.state === 'deleting' }
							disabled={ busy }
						>
							{ __(
								'Delete check data',
								'woocommerce-for-japan'
							) }
						</Button>
					) }
				</div>
			) }
		</PanelBody>
	);
};

export default SecurityScan;
