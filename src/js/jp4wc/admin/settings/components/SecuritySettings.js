/**
 * Security Settings Component
 *
 * The result of the security check (issue #259): the score of the four
 * requirements of the Credit Card Security Guidelines, the items that need
 * action, and a re-check, which runs in WP-Cron while this tab waits for it.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	Button,
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
	SEVERITY_ORDER,
	REQUIREMENT_ORDER,
	ratingSeverity,
	requirementLabels,
	severityLabels,
	checkLabels,
} from './security-labels';

/**
 * How often the tab asks for the result while a re-check runs, and for how long.
 */
const POLL_INTERVAL = 5000;
const POLL_TIMEOUT = 180000;

/**
 * Credit line the Wordfence Intelligence terms ask for, shown as is.
 */
const ATTRIBUTION = 'Vulnerability data: Wordfence Intelligence';

/**
 * Terms of use of wc.artws.info, which cover the vulnerability feed.
 */
const TERMS_URL = 'https://wc.artws.info/security-assessment-term/';

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
 * Returns the rows of the items that need action, most severe first: one row
 * per failed check, and for V-01 one row per vulnerability record found.
 *
 * @param {Object} view Result of GET /jp4wc/v1/security/findings.
 * @return {Array<Object>} Rows.
 */
const findingRows = ( view ) => {
	const rows = [];
	view.checks.forEach( ( check ) => {
		if ( check.status !== 'fail' ) {
			return;
		}
		if ( check.check_id === 'V-01' ) {
			const before = rows.length;
			( view.vulnerabilities?.items || [] ).forEach( ( item, index ) => {
				item.vulnerabilities.forEach( ( record ) => {
					rows.push( {
						key: `V-01:${ index }:${ record.id }`,
						severity: ratingSeverity( record.rating ),
						item,
						record,
					} );
				} );
			} );
			if ( rows.length > before ) {
				return;
			}
		}
		rows.push( {
			key: check.check_id,
			severity: check.severity,
			check,
		} );
	} );

	// Array.prototype.sort() is stable, so the order of the checks stays within a severity.
	const rank = ( severity ) => {
		const index = SEVERITY_ORDER.indexOf( severity );
		return index === -1 ? SEVERITY_ORDER.length : index;
	};
	return rows.sort( ( a, b ) => rank( a.severity ) - rank( b.severity ) );
};

/**
 * Returns how to fix a vulnerability record.
 *
 * @param {Object} item   Affected software.
 * @param {Object} record Vulnerability record.
 * @return {string} Text.
 */
const recordFix = ( item, record ) => {
	if ( ! record.patched ) {
		if ( item.type === 'core' ) {
			return __(
				'No fixed version yet. Update WordPress as soon as its security release is out.',
				'woocommerce-for-japan'
			);
		}
		return __(
			'No fixed version yet. Deactivate and delete it, or replace it with another plugin or theme.',
			'woocommerce-for-japan'
		);
	}
	if ( record.patched_versions.length === 0 ) {
		return __(
			'Update it to the latest version.',
			'woocommerce-for-japan'
		);
	}
	return sprintf(
		/* translators: 1: plugin, theme or WordPress name, 2: comma-separated versions that fix the vulnerability */
		__(
			'Update %1$s to a fixed version (%2$s) or later.',
			'woocommerce-for-japan'
		),
		item.name,
		record.patched_versions.join( ', ' )
	);
};

/**
 * Returns the label of a status of a requirement or a check.
 *
 * @param {string} status pass, action or fail, unknown.
 * @return {string} Label.
 */
const statusLabel = ( status ) => {
	switch ( status ) {
		case 'pass':
			return __( 'Satisfied', 'woocommerce-for-japan' );
		case 'action':
		case 'fail':
			return __( 'Action needed', 'woocommerce-for-japan' );
		default:
			return __( 'Unconfirmed', 'woocommerce-for-japan' );
	}
};

/**
 * The total score and the cards of the four requirements.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element} Element.
 */
const Scorecard = ( { view } ) => {
	const names = requirementLabels();
	return (
		<div className="jp4wc-security-scorecard">
			<div className="jp4wc-security-total">
				<span className="jp4wc-security-total-label">
					{ __( 'Total score', 'woocommerce-for-japan' ) }
				</span>
				<span className="jp4wc-security-total-score">
					{ view.score.total }
					<small> / 100</small>
				</span>
				<span className="jp4wc-security-total-note">
					{ __(
						'The lowest score of the four requirements.',
						'woocommerce-for-japan'
					) }
				</span>
			</div>
			<ul className="jp4wc-security-requirements">
				{ REQUIREMENT_ORDER.map( ( key ) => {
					const requirement = view.requirements[ key ];
					if ( ! requirement ) {
						return null;
					}
					return (
						<li
							key={ key }
							className={ `jp4wc-security-requirement is-${ requirement.status }` }
						>
							<span className="jp4wc-security-requirement-name">
								{ names[ key ] }
							</span>
							<span className="jp4wc-security-requirement-score">
								{ requirement.score }
								<small> / 100</small>
							</span>
							<span
								className={ `jp4wc-security-badge is-${ requirement.status }` }
							>
								{ statusLabel( requirement.status ) }
							</span>
							<span className="jp4wc-security-requirement-counts">
								{ sprintf(
									/* translators: 1: checks passed, 2: checks that need action, 3: checks that could not be run */
									__(
										'Passed %1$d / Action needed %2$d / Unconfirmed %3$d',
										'woocommerce-for-japan'
									),
									requirement.pass,
									requirement.fail,
									requirement.unknown
								) }
							</span>
						</li>
					);
				} ) }
			</ul>
		</div>
	);
};

/**
 * The table of the items that need action.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element} Element.
 */
const FindingsTable = ( { view } ) => {
	const rows = findingRows( view );
	const checks = checkLabels();
	const severities = severityLabels();
	const unknown = view.checks.some( ( check ) => check.status === 'unknown' );

	if ( rows.length === 0 ) {
		return (
			<p className="jp4wc-security-no-findings">
				{ __( 'No items need action.', 'woocommerce-for-japan' ) }
				{ unknown &&
					' ' +
						__(
							'Some checks could not be run (see "All checks" below).',
							'woocommerce-for-japan'
						) }
			</p>
		);
	}

	return (
		<div className="jp4wc-security-findings-wrap">
			<table className="widefat striped jp4wc-security-findings">
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Severity', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'Item', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'Fixed version', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'How to fix', 'woocommerce-for-japan' ) }
						</th>
						<th scope="col">
							{ __( 'Reference', 'woocommerce-for-japan' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ rows.map( ( row ) => (
						<tr key={ row.key }>
							<td>
								<span
									className={ `jp4wc-security-severity is-${ row.severity }` }
								>
									{ severities[ row.severity ] ||
										row.severity }
								</span>
							</td>
							{ row.record ? (
								<>
									<td>
										<a href={ row.item.manage_url }>
											{ row.item.name }
										</a>{ ' ' }
										{ row.item.version }
										<br />
										<span className="description">
											{ row.record.title }
										</span>
									</td>
									<td>
										{ row.record.patched
											? row.record.patched_versions.join(
													', '
											  ) || '—'
											: __(
													'None',
													'woocommerce-for-japan'
											  ) }
									</td>
									<td>
										{ recordFix( row.item, row.record ) }
									</td>
									<td>
										{ /* The data license asks for a link to the record wherever it is shown. */ }
										<a
											href={ row.record.reference }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __(
												'Details',
												'woocommerce-for-japan'
											) }
										</a>
									</td>
								</>
							) : (
								<>
									<td>
										{ checks[ row.check.check_id ]?.title ||
											row.check.check_id }
									</td>
									<td>—</td>
									<td>
										{ checks[ row.check.check_id ]?.fix ||
											'' }
									</td>
									<td>—</td>
								</>
							) }
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
};

/**
 * Every check with its status, by requirement.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element} Element.
 */
const AllChecks = ( { view } ) => {
	const names = requirementLabels();
	const checks = checkLabels();
	return (
		<PanelBody
			title={ __( 'All checks', 'woocommerce-for-japan' ) }
			initialOpen={ false }
		>
			{ REQUIREMENT_ORDER.map( ( key ) => {
				const list = view.checks.filter(
					( check ) => check.requirement === key
				);
				if ( list.length === 0 ) {
					return null;
				}
				return (
					<div key={ key } className="jp4wc-security-check-group">
						<h4>{ names[ key ] }</h4>
						<ul className="jp4wc-security-checks">
							{ list.map( ( check ) => (
								<li key={ check.check_id }>
									<span
										className={ `jp4wc-security-badge is-${ check.status }` }
									>
										{ check.status === 'pass'
											? __(
													'Passed',
													'woocommerce-for-japan'
											  )
											: statusLabel( check.status ) }
									</span>{ ' ' }
									{ checks[ check.check_id ]?.title ||
										check.check_id }
								</li>
							) ) }
						</ul>
					</div>
				);
			} ) }
			<p className="description">
				{ __(
					'"Unconfirmed" means the check could not be run on this site, for example because the server cannot send requests to itself. It costs no points.',
					'woocommerce-for-japan'
				) }
			</p>
		</PanelBody>
	);
};

/**
 * What the tab says about the vulnerability data.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check.
 * @return {Element|null} Element.
 */
const FeedStatus = ( { view } ) => {
	if ( view.feed.unavailable ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ sprintf(
					/* translators: %s: host name of the vulnerability feed, e.g. wc.artws.info */
					__(
						'The vulnerability data could not be downloaded for several days, so the installed software is not checked for known vulnerabilities. Make sure this server can connect to %s.',
						'woocommerce-for-japan'
					),
					view.feed.host
				) }
			</Notice>
		);
	}
	if ( ! view.vulnerabilities ) {
		return (
			<Notice status="info" isDismissible={ false }>
				{ __(
					'The vulnerability data has not been downloaded yet, so known vulnerabilities are not checked. It is downloaded once a day.',
					'woocommerce-for-japan'
				) }
			</Notice>
		);
	}
	if ( view.vulnerabilities.stale ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'The vulnerability data has not been updated for a while, so vulnerabilities disclosed since then are not checked.',
					'woocommerce-for-japan'
				) }
			</Notice>
		);
	}
	return null;
};

/**
 * The credit line and the copyright notices of the vulnerability data, as its license asks.
 *
 * @param {Object} props      Props.
 * @param {Object} props.view Result of the security check, or null.
 * @return {Element} Element.
 */
const Credits = ( { view } ) => (
	<div className="jp4wc-security-credits">
		<p className="description">{ ATTRIBUTION }</p>
		{ ( view?.vulnerabilities?.copyrights || [] ).map( ( copyright ) => (
			<details key={ copyright.notice }>
				<summary>{ copyright.notice }</summary>
				<p>{ copyright.license }</p>
				<p>
					<a
						href={ copyright.license_url }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'License', 'woocommerce-for-japan' ) }
					</a>
				</p>
			</details>
		) ) }
	</div>
);

const SecuritySettings = ( {
	settings,
	updateSetting,
	saveSettings,
	saving,
} ) => {
	const [ view, setView ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState( null );
	// idle, waiting (for the re-check), done, timeout or error.
	const [ recheck, setRecheck ] = useState( { state: 'idle' } );
	const pollTimer = useRef( null );
	const mounted = useRef( true );

	const loadView = async () => {
		const response = await apiFetch( {
			path: '/jp4wc/v1/security/findings',
			method: 'GET',
		} );
		if ( mounted.current ) {
			setView( response );
		}
		return response;
	};

	// Asks for the result until the re-check asked for at requestedAt has run.
	const waitForRecheck = ( requestedAt, startedAt ) => {
		clearTimeout( pollTimer.current );
		pollTimer.current = setTimeout( async () => {
			try {
				const response = await loadView();
				if ( ! mounted.current ) {
					return;
				}
				if ( ! response.enabled ) {
					setRecheck( { state: 'idle' } );
					return;
				}
				if ( response.recheck.done_at >= requestedAt ) {
					setRecheck( { state: 'done' } );
					return;
				}
			} catch ( err ) {
				// Ask again on the next round.
			}
			if ( ! mounted.current ) {
				return;
			}
			if ( Date.now() - startedAt >= POLL_TIMEOUT ) {
				setRecheck( { state: 'timeout' } );
				return;
			}
			waitForRecheck( requestedAt, startedAt );
		}, POLL_INTERVAL );
	};

	useEffect( () => {
		mounted.current = true;
		loadView()
			.then( ( response ) => {
				if ( mounted.current && response.recheck?.pending ) {
					setRecheck( { state: 'waiting' } );
					waitForRecheck( response.recheck.requested_at, Date.now() );
				}
			} )
			.catch( ( err ) => {
				if ( mounted.current ) {
					setLoadError(
						err.message ||
							__(
								'The result of the security check could not be loaded.',
								'woocommerce-for-japan'
							)
					);
				}
			} )
			.finally( () => {
				if ( mounted.current ) {
					setLoading( false );
				}
			} );
		return () => {
			mounted.current = false;
			clearTimeout( pollTimer.current );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const startRecheck = async () => {
		setRecheck( { state: 'waiting' } );
		try {
			const response = await apiFetch( {
				path: '/jp4wc/v1/security/rescan',
				method: 'POST',
			} );
			if ( ! mounted.current ) {
				return;
			}
			setView( response );
			waitForRecheck( response.recheck.requested_at, Date.now() );
		} catch ( err ) {
			if ( mounted.current ) {
				setRecheck( {
					state: 'error',
					message:
						err.message ||
						__(
							'The re-check could not be started.',
							'woocommerce-for-japan'
						),
				} );
			}
		}
	};

	const handleSave = async () => {
		await saveSettings( settings );
		// Turning the check off removes its results; turning it on schedules a check.
		try {
			await loadView();
		} catch ( err ) {
			// The previous result stays on screen.
		}
	};

	const enabled = view?.enabled;

	return (
		<div className="jp4wc-security-settings">
			{ loading && (
				<div className="jp4wc-security-loading">
					<Spinner />
				</div>
			) }

			{ loadError && (
				<Notice status="error" isDismissible={ false }>
					{ loadError }
				</Notice>
			) }

			{ view && ! enabled && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'The security check is off. Turn it on below and save the settings to check this site.',
						'woocommerce-for-japan'
					) }
				</Notice>
			) }

			{ view && enabled && (
				<>
					<FeedStatus view={ view } />

					{ view.requirements ? (
						<>
							<Scorecard view={ view } />
							<p className="jp4wc-security-disclaimer description">
								{ __(
									'This check shows what needs attention. It does not guarantee compliance with the Credit Card Security Guidelines.',
									'woocommerce-for-japan'
								) }
							</p>
						</>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'This site has not been checked yet. The first check runs shortly in WP-Cron, or start it with "Re-check now".',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }

					<div className="jp4wc-security-recheck">
						<Button
							variant="secondary"
							onClick={ startRecheck }
							isBusy={ recheck.state === 'waiting' }
							disabled={ recheck.state === 'waiting' }
						>
							{ recheck.state === 'waiting'
								? __( 'Re-checking…', 'woocommerce-for-japan' )
								: __(
										'Re-check now',
										'woocommerce-for-japan'
								  ) }
						</Button>
						<span className="description">
							{ view.checked_at > 0 &&
								sprintf(
									/* translators: %s: date and time of the last check */
									__(
										'Last checked: %s',
										'woocommerce-for-japan'
									),
									formatTime( view.checked_at )
								) }
							{ view.vulnerabilities?.generated_at &&
								' / ' +
									sprintf(
										/* translators: %s: date and time the vulnerability data was generated */
										__(
											'Vulnerability data: %s',
											'woocommerce-for-japan'
										),
										formatTime(
											view.vulnerabilities.generated_at
										)
									) }
						</span>
					</div>

					{ recheck.state === 'waiting' && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'The re-check runs in the background. The results appear here when it finishes.',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }
					{ recheck.state === 'done' && (
						<Notice
							status="success"
							onRemove={ () => setRecheck( { state: 'idle' } ) }
						>
							{ __(
								'The re-check has finished.',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }
					{ recheck.state === 'timeout' && (
						<Notice
							status="warning"
							onRemove={ () => setRecheck( { state: 'idle' } ) }
						>
							{ __(
								'The re-check has not finished yet. It runs with WP-Cron, so reload this page later to see the results.',
								'woocommerce-for-japan'
							) }
						</Notice>
					) }
					{ recheck.state === 'error' && (
						<Notice
							status="error"
							onRemove={ () => setRecheck( { state: 'idle' } ) }
						>
							{ recheck.message }
						</Notice>
					) }

					{ view.requirements && (
						<>
							<h3>
								{ __(
									'Items that need action',
									'woocommerce-for-japan'
								) }
							</h3>
							<FindingsTable view={ view } />
							<AllChecks view={ view } />
						</>
					) }
				</>
			) }

			<PanelBody
				title={ __( 'Settings', 'woocommerce-for-japan' ) }
				initialOpen={ true }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Security check',
							'woocommerce-for-japan'
						) }
						help={
							__(
								'Once a day, download the list of known vulnerabilities from wc.artws.info (Artisan Workshop) and check the installed plugins, themes and WordPress against it. Only the versions of this plugin, WordPress, WooCommerce and PHP are sent, in the User-Agent header.',
								'woocommerce-for-japan'
							) +
							' ' +
							__(
								'Turning it off also stops the checks on this tab and deletes their results.',
								'woocommerce-for-japan'
							)
						}
						checked={ settings?.[ 'security-scan' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'security-scan', value ? '1' : '' )
						}
					/>
				</PanelRow>
				<PanelRow>
					<p className="description">
						{ createInterpolateElement(
							__(
								'The vulnerability data comes from wc.artws.info and is subject to its <a>Terms of Use</a>.',
								'woocommerce-for-japan'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content -- createInterpolateElement() puts the translated words inside.
									<a
										href={ TERMS_URL }
										target="_blank"
										rel="noopener noreferrer"
									/>
								),
							}
						) }
					</p>
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
						? // eslint-disable-next-line @wordpress/i18n-ellipsis -- The string of the other tabs.
						  __( 'Saving...', 'woocommerce-for-japan' )
						: __( 'Save Settings', 'woocommerce-for-japan' ) }
				</Button>
			</div>

			<Credits view={ view } />
		</div>
	);
};

export default SecuritySettings;
