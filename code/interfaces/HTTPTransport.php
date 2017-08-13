<?php

interface DelectusHTTPTransportInterface extends DelectusTransportInterface {
	// content type we send and receive fromt he Delectus service
	const ContentType = 'application/json';

	// keys so these can be changed in config, not used to generate the headers
	const ContentTypeHeader = 'ContentType';

	const ClientTokenParameter = 'ct';
	const ClientTokenFieldName = 'X-Client-Token';

	const AuthTokenParameter = 'at';
	const AuthTokenHeader    = 'X-Client-Auth';

	const SiteIdentifierParameter = 'si';
	const SiteIdentifierHeader    = 'X-Site-Identifier';

	const RequestTokenParameter = 'rt';
	const RequestTokenHeader    = 'X-Request-Token';

	const RequestItemInfoKey  = 'item';
	const RequestEncryptedKey = 'encrypted';

}