#!/bin/bash

# Script to deploy HostForge to k3d
# Usage: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-local}
NAMESPACE="hostforge"

echo "🚀 Deploying HostForge to k3d (Environment: $ENVIRONMENT)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if k3d is installed
if ! command -v k3d &> /dev/null; then
    print_error "k3d is not installed. Install it with: curl -s https://raw.githubusercontent.com/k3d-io/k3d/main/install.sh | bash"
    exit 1
fi

# Check if kubectl is installed
if ! command -v kubectl &> /dev/null; then
    print_error "kubectl is not installed. Install it first."
    exit 1
fi

# Check if cluster exists, create if not
if ! k3d cluster list | grep -q "hostforge"; then
    print_warning "Creating k3d cluster 'hostforge'..."
    k3d cluster create hostforge \
        --api-port 6550 \
        --servers 1 \
        --agents 2 \
        --port "8080:80@loadbalancer" \
        --port "8443:443@loadbalancer" \
        --volume "$(pwd):/app@server:0" \
        --wait
    print_status "k3d cluster created"
else
    print_status "k3d cluster 'hostforge' already exists"
fi

# Set kubectl context
kubectl config use-context k3d-hostforge

# Apply Kubernetes manifests
print_status "Applying Kubernetes manifests..."

kubectl apply -f k8s/00-namespace.yaml
print_status "Namespace created"

kubectl apply -f k8s/01-configmap.yaml
print_status "ConfigMap applied"

# Check if secrets need to be updated
if kubectl get secret hostforge-secrets -n $NAMESPACE &> /dev/null; then
    print_warning "Secrets already exist. Skipping..."
else
    print_warning "Creating secrets. Remember to update them with real values!"
    kubectl apply -f k8s/02-secrets.yaml
fi

kubectl apply -f k8s/03-pvc.yaml
print_status "PVCs created"

kubectl apply -f k8s/04-postgresql.yaml
print_status "PostgreSQL deployed"

kubectl apply -f k8s/05-redis.yaml
print_status "Redis deployed"

# Wait for database to be ready
print_status "Waiting for PostgreSQL to be ready..."
kubectl wait --for=condition=ready pod -l app=postgresql -n $NAMESPACE --timeout=120s

print_status "Waiting for Redis to be ready..."
kubectl wait --for=condition=ready pod -l app=redis -n $NAMESPACE --timeout=60s

kubectl apply -f k8s/06-app-deployment.yaml
print_status "Application deployed"

kubectl apply -f k8s/07-workers.yaml
print_status "Workers deployed"

kubectl apply -f k8s/08-ingress.yaml
print_status "Ingress configured"

kubectl apply -f k8s/09-hpa.yaml
print_status "HPA configured"

kubectl apply -f k8s/10-policies.yaml
print_status "Policies applied"

# Wait for application to be ready
print_status "Waiting for application pods to be ready..."
kubectl wait --for=condition=ready pod -l app=hostforge,component=app -n $NAMESPACE --timeout=180s

# Get the application URL
echo ""
print_status "✅ Deployment complete!"
echo ""
echo "📊 Cluster Information:"
echo "-----------------------------------"
kubectl get pods -n $NAMESPACE
echo ""
echo "🌐 Access the application:"
echo "   URL: http://hostforge.local:8080"
echo ""
echo "💡 Add to /etc/hosts:"
echo "   127.0.0.1 hostforge.local"
echo ""
echo "🔧 Useful commands:"
echo "   View logs: kubectl logs -f -l app=hostforge,component=app -n $NAMESPACE"
echo "   Get pods: kubectl get pods -n $NAMESPACE"
echo "   Exec into pod: kubectl exec -it <pod-name> -n $NAMESPACE -- sh"
echo "   Port forward: kubectl port-forward svc/hostforge-app 8080:80 -n $NAMESPACE"
echo ""
echo "🗑️  To delete:"
echo "   k3d cluster delete hostforge"
echo ""
