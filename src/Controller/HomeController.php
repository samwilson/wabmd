<?php

namespace App\Controller;

use App\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {

	private $db;

	public function __construct( Database $database ) {
		$this->db = $database;
	}

	#[Route( '/', name: 'home' )]
	public function index( Request $request ): Response {
		$results = $this->db->search( $request->get( 'surname' ) );

		return $this->render( 'search.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'results' => $results,
		] );
	}

	#[Route( '/{type}/{year}/{num}', name: 'record' )]
	public function record( string $type, string $year, string $num ): Response {
		return $this->render( 'view.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'type' => $type,
			'record' => $this->db->getRecord( $type, $year, $num ),
		] );
	}
}
