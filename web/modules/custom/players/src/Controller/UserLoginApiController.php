<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Firebase\JWT\JWT;

class UserLoginApiController extends ControllerBase {

  private $key = 'your_secret_key'; // Store this securely, preferably in configuration
  public function login(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $user = user_load_by_name($username);

    if ($user && $user->id() !== 0 && \Drupal::service('user.auth')->authenticate($username, $password)) {
      $token = $this->generateToken($user);
      return new JsonResponse(['token' => $token]);
    } else {
      return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }
  }

  public function logout() {
    // For token-based auth, the client typically just discards the token
    // Here we could potentially blacklist the token if needed
    return new JsonResponse(['message' => 'Logged out successfully']);
  }

  public function getUserInfo() {
    $user = \Drupal::currentUser();
    return new JsonResponse([
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
    ]);
  }

  private function generateToken($user) {
    $payload = [
      'uid' => $user->id(),
      'username' => $user->getAccountName(),
      'exp' => time() + (60 * 60), // Token expires in 1 hour
    ];
    return JWT::encode($payload, $this->key, 'HS256');
  }
}
