interface IButtonConfigurationOptions {
	alignment: string | 'inherit';
	width: number;
	borderRadius: number;

	clickID?: string;
	isMiniCart: boolean;
	isShortcode: boolean;

	shortcodeInfo?: {
		productId: number;
		quantity: number;
	};
}
